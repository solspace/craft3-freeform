<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields;

use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\Assets;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Types\FileUploadType;
use Solspace\Freeform\Bundles\GraphQL\Types\Inputs\FileUploadInputType;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\MultipleValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\FileUploadTrait;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\MultipleValueTrait;
use Solspace\Freeform\Library\Exceptions\FieldExceptions\FileUploadException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class FileUploadField extends AbstractField implements MultipleValueInterface, FileUploadInterface
{
    use FileUploadTrait;
    use MultipleValueTrait;

    public const DEFAULT_MAX_FILESIZE_KB = 2048;
    public const DEFAULT_FILE_COUNT = 1;

    public const FILE_KEYS = [
        'name',
        'tmp_name',
        'error',
        'size',
        'type',
    ];

    /** @var array */
    protected $fileKinds;

    /** @var int */
    protected $maxFileSizeKB;

    /** @var int */
    protected $fileCount;

    /**
     * Cache for handles meant for preventing duplicate file uploads when calling ::validate() and ::uploadFile()
     * Stores the assetID once as value for handle key.
     *
     * @var array
     */
    private static $filesUploaded = [];

    /**
     * Contains any errors for a given upload field.
     *
     * @var array
     */
    private static $filesUploadedErrors = [];

    public static function getFieldType(): string
    {
        return self::TYPE_FILE;
    }

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_FILE;
    }

    public function getAssets(): AssetQuery
    {
        return Asset::find()->id($this->getValue());
    }

    public function getFileKinds(): array
    {
        if (!\is_array($this->fileKinds)) {
            return [];
        }

        return $this->fileKinds;
    }

    public function getMaxFileSizeKB(): int
    {
        return $this->maxFileSizeKB ?: self::DEFAULT_MAX_FILESIZE_KB;
    }

    public function getMaxFileSizeBytes(): int
    {
        return $this->getMaxFileSizeKB() * 1000;
    }

    public function getFileCount(): int
    {
        return $this->fileCount <= 1 ? 1 : (int) $this->fileCount;
    }

    public function getInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        $inputAttributes = $this->getInputAttributesString().$attributes->getInputAttributesAsString();
        $name = $this->getAttributeString('name', $this->getHandle().'[]');
        $type = $this->getAttributeString('type', $this->getType());
        $id = $this->getAttributeString('id', $this->getIdAttribute());
        $isMultiple = $this->getParameterString('multiple', $this->getFileCount() > 1);
        $isRequired = $this->getRequiredAttribute();

        return '<input '.$inputAttributes.$name.$type.$id.$isMultiple.$isRequired.'/>';
    }

    /**
     * Attempt to upload the file to its respective location.
     *
     * @return null|array - asset IDs
     *
     * @throws FileUploadException
     */
    public function uploadFile()
    {
        if (!isset(self::$filesUploaded[$this->handle])) {
            $response = $this->getForm()->getFileUploadHandler()->uploadFile($this, $this->getForm());

            self::$filesUploaded[$this->handle] = null;
            self::$filesUploadedErrors[$this->handle] = [];

            if ($response) {
                $errors = $this->getErrors() ?: [];

                if ($response->getAssetIds() || empty($response->getErrors())) {
                    $this->values = $response->getAssetIds();
                    self::$filesUploaded[$this->handle] = $response->getAssetIds();

                    return $this->values;
                }

                if ($response->getErrors()) {
                    $this->errors = array_merge($errors, $response->getErrors());
                    self::$filesUploadedErrors[$this->handle] = $this->errors;

                    throw new FileUploadException(implode('. ', $response->getErrors()));
                }

                $this->errors = array_merge($errors, $response->getErrors());
                self::$filesUploadedErrors[$this->handle] = $this->errors;

                throw new FileUploadException($this->translate('Could not upload file'));
            }

            return null;
        }

        if (!empty(self::$filesUploadedErrors[$this->handle])) {
            $this->errors = self::$filesUploadedErrors[$this->handle];
        }

        return self::$filesUploaded[$this->handle];
    }

    public function getContentGqlType(): Type|array
    {
        return Type::listOf(FileUploadType::getType());
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $description = $this->getContentGqlDescription();

        if (1 === $this->getFileCount()) {
            $description[] = 'Only 1 file can be uploaded at once.';
        } else {
            $description[] = 'Multiple files can be uploaded at once.';
        }

        $description[] = 'File types include '.implode(', ', $this->getFileKinds()).'.';
        $description[] = 'Max file size is '.$this->getMaxFileSizeKB().'KB.';

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => FileUploadInputType::getType(),
            'description' => trim($description),
        ];
    }

    /**
     * @throws Exception
     */
    public function validateGraphQL(): void
    {
        $uploadedFiles = 0;

        $uploadErrors = [];

        $handle = $this->handle;

        $validExtensions = $this->getValidExtensions();

        $arguments = $this->getForm()->getGraphQLArguments();

        $filesService = $this->getForm()->getFileUploadHandler();

        if (isset($arguments[$handle]) && !$this->isHidden()) {
            $fileCount = \count($arguments[$handle]);

            if ($fileCount > $this->getFileCount()) {
                $uploadErrors[] = $this->translate('Tried uploading {count} files. Maximum {max} files allowed.', [
                    'count' => $fileCount,
                    'max' => $this->getFileCount(),
                ]);
            }

            foreach ($arguments[$handle] as &$fileUpload) {
                if (!empty($fileUpload['fileData'])) {
                    $matches = $filesService->extractBase64String($fileUpload);
                    $fileData = base64_decode($matches['data']);

                    if ($fileData) {
                        if (empty($fileUpload['filename'])) {
                            // Make up a filename
                            $fileUpload['filename'] = 'Upload 1';
                        }

                        $filename = Assets::prepareAssetName($fileUpload['filename']);
                        $extension = pathinfo($filename, \PATHINFO_EXTENSION);

                        // Valid the extension
                        if (!\in_array(strtolower($extension), $validExtensions, true)) {
                            $uploadErrors[] = $this->translate("'{extension}' is not an allowed file extension", [
                                'extension' => $extension,
                            ]);
                        }

                        // Cannot get the file size without moving to temp folder
                        $tempPath = $filesService->moveToBase64FileTempFolder($fileUpload, $extension);

                        $fileSizeKB = ceil(filesize($tempPath) / 1024);

                        if ($fileSizeKB > $this->getMaxFileSizeKB()) {
                            $uploadErrors[] = $this->translate('You tried uploading {fileSize}KB, but the maximum file upload size is {maxFileSize}KB', [
                                'fileSize' => $fileSizeKB,
                                'maxFileSize' => $this->getMaxFileSizeKB(),
                            ]);
                        }

                        ++$uploadedFiles;
                    } else {
                        $uploadErrors[] = $this->translate('Invalid file data provided');
                    }
                } elseif (!empty($fileUpload['url'])) {
                    if (empty($fileUpload['filename'])) {
                        // Make up a filename
                        $url = parse_url($fileUpload['url']);
                        $filename = pathinfo($url['path'], \PATHINFO_FILENAME);
                        $extension = pathinfo($url['path'], \PATHINFO_EXTENSION);

                        $fileUpload['filename'] = $filename.'.'.$extension;
                    }

                    ++$uploadedFiles;
                }
            }
        }

        if (!$uploadedFiles && $this->isRequired() && !$this->isHidden()) {
            $uploadErrors[] = $this->translate('This field is required');
        }

        // if there are errors - prevent the file from being uploaded
        if ($uploadErrors || $this->isHidden()) {
            self::$filesUploaded[$handle] = null;
        }

        self::$filesUploadedErrors[$handle] = $uploadErrors;

        $this->getForm()->setGraphQLArguments($arguments);
    }

    /**
     * @throws InvalidConfigException
     */
    public function validateFiles(): void
    {
        $uploadedFiles = 0;

        $uploadErrors = [];

        $handle = $this->handle;

        $exists = isset($_FILES[$handle]) && !empty($_FILES[$handle]['name']) && !$this->isHidden();

        if ($exists && !\is_array($_FILES[$handle]['name'])) {
            foreach (self::FILE_KEYS as $key) {
                $_FILES[$handle][$key] = [$_FILES[$handle][$key]];
            }
        }

        if ($exists && is_countable($_FILES[$handle]['name'])) {
            $fileCount = \count($_FILES[$handle]['name']);

            if ($fileCount > $this->getFileCount()) {
                $uploadErrors[] = $this->translate(
                    'Tried uploading {count} files. Maximum {max} files allowed.',
                    ['max' => $this->getFileCount(), 'count' => $fileCount]
                );
            }

            foreach ($_FILES[$handle]['name'] as $index => $name) {
                $extension = pathinfo($name, \PATHINFO_EXTENSION);
                $validExtensions = $this->getValidExtensions();

                $tmpName = $_FILES[$handle]['tmp_name'][$index];
                $errorCode = $_FILES[$handle]['error'][$index];

                if (empty($tmpName) && \UPLOAD_ERR_NO_FILE === $errorCode) {
                    continue;
                }

                if (empty($tmpName)) {
                    switch ($errorCode) {
                        case \UPLOAD_ERR_INI_SIZE:
                        case \UPLOAD_ERR_FORM_SIZE:
                            $uploadErrors[] = $this->translate('File size too large');

                            break;

                        case \UPLOAD_ERR_PARTIAL:
                            $uploadErrors[] = $this->translate('The file was only partially uploaded');

                            break;
                    }
                    $uploadErrors[] = $this->translate('Could not upload file');
                }

                // Check for the correct file extension
                if (!\in_array(strtolower($extension), $validExtensions, true)) {
                    $uploadErrors[] = $this->translate(
                        "'{extension}' is not an allowed file extension",
                        ['extension' => $extension]
                    );
                }

                $fileSizeKB = ceil($_FILES[$handle]['size'][$index] / 1024);
                if ($fileSizeKB > $this->getMaxFileSizeKB()) {
                    $uploadErrors[] = $this->translate(
                        'You tried uploading {fileSize}KB, but the maximum file upload size is {maxFileSize}KB',
                        ['fileSize' => $fileSizeKB, 'maxFileSize' => $this->getMaxFileSizeKB()]
                    );
                }

                ++$uploadedFiles;
            }
        }

        if (!$uploadedFiles && $this->isRequired() && !$this->isHidden()) {
            $uploadErrors[] = $this->translate('This field is required');
        }

        // if there are errors - prevent the file from being uploaded
        if ($uploadErrors || $this->isHidden()) {
            self::$filesUploaded[$handle] = null;
        }

        self::$filesUploadedErrors[$handle] = $uploadErrors;
    }

    /**
     * Validate the field and add error messages if any.
     *
     * @throws Exception
     */
    protected function validate(): array
    {
        if (!isset(self::$filesUploaded[$this->handle])) {
            if ($this->getForm()->isGraphQLPosted()) {
                $this->validateGraphQL();
            } else {
                $this->validateFiles();
            }
        }

        return self::$filesUploadedErrors[$this->handle];
    }

    /**
     * Returns an array of all valid file extensions for this field.
     */
    protected function getValidExtensions(): array
    {
        $allFileKinds = $this->getForm()->getFileUploadHandler()->getFileKinds();

        $selectedFileKinds = $this->getFileKinds();

        $allowedExtensions = [];
        if ($selectedFileKinds) {
            foreach ($selectedFileKinds as $kind) {
                if (isset($allFileKinds[$kind])) {
                    $allowedExtensions = array_merge($allowedExtensions, $allFileKinds[$kind]);
                }
            }
        } else {
            $allowedExtensions = \Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;
        }

        return $allowedExtensions;
    }
}
