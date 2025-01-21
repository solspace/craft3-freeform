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

namespace Solspace\Freeform\Services;

use craft\db\Query;
use craft\elements\Asset;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\errors\UploadFailedException;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\web\UploadedFile;
use GuzzleHttp\Exception\GuzzleException;
use Solspace\Freeform\Bundles\Form\Security\FormSecret;
use Solspace\Freeform\Events\Files\UploadEvent;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\Pro\FileDragAndDropField;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\FileUploads\FileUploadHandlerInterface;
use Solspace\Freeform\Library\FileUploads\FileUploadResponse;
use Solspace\Freeform\Records\UnfinalizedFileRecord;
use yii\base\ErrorException;
use yii\base\Exception;

class FilesService extends BaseService implements FileUploadHandlerInterface
{
    public const CLEANUP_CACHE_KEY = 'freeform_file_cleanup_cache_key';
    public const CACHE_TTL = 3600; // 1 hour

    public const EVENT_BEFORE_UPLOAD = 'beforeUpload';
    public const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * Uploads a file and flags it as "unfinalized"
     * It will be finalized only after the form has been submitted fully
     * All unfinalized files will be deleted after a certain amount of time.
     *
     * @throws InvalidSubpathException|\Throwable
     */
    public function uploadFile(FileUploadField $field, Form $form): ?FileUploadResponse
    {
        if (!$field->getAssetSourceId()) {
            return null;
        }

        if ($form->isGraphQLPosted()) {
            return $this->uploadGraphQL($field, $form);
        }

        if (!$_FILES || !isset($_FILES[$field->getHandle()])) {
            return null;
        }

        if (!is_countable($_FILES[$field->getHandle()]['name'])) {
            return null;
        }

        $uploadedFileCount = \count($_FILES[$field->getHandle()]['name']);

        $beforeUploadEvent = new UploadEvent($field);
        $this->trigger(self::EVENT_BEFORE_UPLOAD, $beforeUploadEvent);

        if (!$beforeUploadEvent->isValid) {
            return null;
        }

        $folder = $this->getFileUploadFolder($form, $field);

        $uploadedAssetIds = $errors = [];
        for ($i = 0; $i < $uploadedFileCount; ++$i) {
            $uploadedFile = UploadedFile::getInstanceByName($field->getHandle()."[{$i}]");

            if (!$uploadedFile) {
                continue;
            }

            $asset = $response = null;

            try {
                $filename = Assets::prepareAssetName($uploadedFile->name);
                $asset = new Asset();

                // Move the uploaded file to the temp folder
                try {
                    $tempPath = $uploadedFile->saveAsTempFile();
                } catch (ErrorException $e) {
                    throw new UploadFailedException(0, null, $e);
                }

                $asset->kind = AssetsHelper::getFileKindByExtension($uploadedFile->name);
                $asset->tempFilePath = $tempPath;
                $asset->filename = $filename;
                $asset->setScenario(Asset::SCENARIO_CREATE);
                $asset->newFolderId = $folder->id;
                $asset->setVolumeId($folder->volumeId);
                $asset->avoidFilenameConflicts = true;
                $asset->uploaderId = \Craft::$app->getUser()->getId();

                $response = \Craft::$app->getElements()->saveElement($asset);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            if ($response) {
                $assetId = $asset->id;
                $this->markAssetUnfinalized($assetId);

                $uploadedAssetIds[] = $assetId;
            } elseif ($asset) {
                $errors = array_merge($errors, $asset->getErrors());
            }
        }

        $field->setValue($uploadedAssetIds);

        $this->trigger(self::EVENT_AFTER_UPLOAD, new UploadEvent($field));

        if ($uploadedAssetIds) {
            return new FileUploadResponse($uploadedAssetIds);
        }

        return new FileUploadResponse(null, $errors);
    }

    /**
     * Uploads a base64 encoded file and flags it as "unfinalized"
     * It will be finalized only after the form has been submitted fully
     * All unfinalized files will be deleted after a certain amount of time.
     *
     * @throws InvalidSubpathException
     * @throws Exception
     * @throws GuzzleException|\Throwable
     */
    public function uploadGraphQL(FileUploadField $field, Form $form): ?FileUploadResponse
    {
        $errors = [];

        $uploadedAssetIds = [];

        $handle = $field->getHandle();

        $arguments = $form->getGraphQLArguments();

        if (!$arguments || !isset($arguments[$handle])) {
            return null;
        }

        $beforeUploadEvent = new UploadEvent($field);
        $this->trigger(self::EVENT_BEFORE_UPLOAD, $beforeUploadEvent);

        if (!$beforeUploadEvent->isValid) {
            return null;
        }

        $folder = $this->getFileUploadFolder($form, $field);

        foreach ($arguments[$handle] as $fileUpload) {
            $asset = null;
            $response = null;
            $filename = null;
            $tempPath = null;

            if (!empty($fileUpload['fileData'])) {
                $filename = Assets::prepareAssetName($fileUpload['filename']);
                $extension = pathinfo($filename, \PATHINFO_EXTENSION);

                $tempPath = $this->moveToBase64FileTempFolder($fileUpload, $extension);
            } elseif (!empty($fileUpload['url'])) {
                $url = $fileUpload['url'];

                if (empty($fileUpload['filename'])) {
                    $filename = AssetsHelper::prepareAssetName(pathinfo(UrlHelper::stripQueryString($url), \PATHINFO_BASENAME));
                } else {
                    $filename = AssetsHelper::prepareAssetName($fileUpload['filename']);
                }

                $extension = pathinfo($filename, \PATHINFO_EXTENSION);

                // Download the file
                $tempPath = AssetsHelper::tempFilePath($extension);

                \Craft::createGuzzleClient()->request('GET', $url, [
                    'sink' => $tempPath,
                ]);
            }

            try {
                $asset = new Asset();
                $asset->kind = AssetsHelper::getFileKindByExtension($filename);
                $asset->tempFilePath = $tempPath;
                $asset->filename = $filename;
                $asset->setScenario(Asset::SCENARIO_CREATE);
                $asset->newFolderId = $folder->id;
                $asset->setVolumeId($folder->volumeId);
                $asset->avoidFilenameConflicts = true;
                $asset->uploaderId = \Craft::$app->getUser()->getId();

                $response = \Craft::$app->getElements()->saveElement($asset);
            } catch (Exception|\Throwable $e) {
                $errors[] = $e->getMessage();
            }

            if ($response) {
                $assetId = $asset->id;
                $this->markAssetUnfinalized($assetId);

                $uploadedAssetIds[] = $assetId;
            } elseif ($asset) {
                $errors = array_merge($errors, $asset->getErrors());
            }
        }

        $field->setValue($uploadedAssetIds);

        $this->trigger(self::EVENT_AFTER_UPLOAD, new UploadEvent($field));

        if ($uploadedAssetIds) {
            return new FileUploadResponse($uploadedAssetIds);
        }

        return new FileUploadResponse(null, $errors);
    }

    public function extractBase64String(array $fileUpload): ?array
    {
        $fileDataString = ArrayHelper::remove($fileUpload, 'fileData');

        if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)/i', $fileDataString, $matches)) {
            return $matches;
        }

        return null;
    }

    /**
     * @throws InvalidSubpathException
     */
    public function getFileUploadFolder(Form $form, FileUploadInterface $field): ?VolumeFolder
    {
        $assetService = \Craft::$app->assets;

        if (!$field->getDefaultUploadLocation()) {
            return $assetService->getRootFolderByVolumeId($field->getAssetSourceId());
        }

        return $this->getFolder($field->getAssetSourceId(), $field->getDefaultUploadLocation(), $form);
    }

    /**
     * @throws Exception
     */
    public function moveToBase64FileTempFolder(array $fileUpload, string $extension): string
    {
        $matches = $this->extractBase64String($fileUpload);

        $fileData = base64_decode($matches['data']);

        $tempPath = AssetsHelper::tempFilePath($extension);

        file_put_contents($tempPath, $fileData);

        return $tempPath;
    }

    /**
     * @throws \Throwable
     * @throws InvalidSubpathException
     */
    public function uploadDragAndDropFile(FileDragAndDropField $field, Form $form): ?Asset
    {
        if (!$field->getAssetSourceId()) {
            return null;
        }

        if (!$_FILES || !isset($_FILES[$field->getHandle()])) {
            return null;
        }

        if (is_countable($_FILES[$field->getHandle()]['name'])) {
            return null;
        }

        $beforeUploadEvent = new UploadEvent($field);
        $this->trigger(self::EVENT_BEFORE_UPLOAD, $beforeUploadEvent);

        if (!$beforeUploadEvent->isValid) {
            return null;
        }

        $folder = $this->getFileUploadFolder($form, $field);

        $errors = [];
        $uploadedFile = UploadedFile::getInstanceByName($field->getHandle());
        if (!$uploadedFile) {
            return null;
        }

        $asset = null;
        $uploadedSuccessfully = false;

        try {
            $filename = Assets::prepareAssetName($uploadedFile->name);
            $asset = new Asset();

            // Move the uploaded file to the temp folder
            try {
                $tempPath = $uploadedFile->saveAsTempFile();
            } catch (ErrorException $e) {
                throw new UploadFailedException(0, null, $e);
            }

            $asset->kind = AssetsHelper::getFileKindByExtension($uploadedFile->name);
            $asset->tempFilePath = $tempPath;
            $asset->filename = $filename;
            $asset->setScenario(Asset::SCENARIO_CREATE);
            $asset->setVolumeId($folder->volumeId);
            $asset->newFolderId = $folder->id;
            $asset->avoidFilenameConflicts = true;
            $asset->uploaderId = \Craft::$app->getUser()->getId();

            $uploadedSuccessfully = \Craft::$app->getElements()->saveElement($asset);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if ($uploadedSuccessfully) {
            $assetId = $asset->id;
            $formToken = FormSecret::get($form);

            $this->markAssetUnfinalized($assetId, $field, $formToken);
        } elseif ($asset) {
            $errors = array_merge($errors, $asset->getErrorSummary());
            $asset = null;
        }

        if (\count($errors)) {
            $field->addErrors($errors);
        }

        $this->trigger(self::EVENT_AFTER_UPLOAD, new UploadEvent($field));

        return $asset;
    }

    public function _volumeIdBySourceKey(string $sourceKey)
    {
        $parts = explode(':', $sourceKey, 2);

        if (2 !== \count($parts)) {
            return null;
        }

        /** @var null|Volume $volume */
        $volume = \Craft::$app->getVolumes()->getVolumeByUid($parts[1]);

        return $volume ? $volume->id : null;
    }

    /**
     * Stores the unfinalized assetId in the database
     * So that it can be deleted later if the form hasn't been finalized.
     *
     * @param mixed $assetId
     */
    public function markAssetUnfinalized($assetId, ?FileUploadField $field = null, ?string $formToken = null)
    {
        $record = new UnfinalizedFileRecord();
        $record->assetId = $assetId;
        $record->fieldHandle = $field ? $field->getHandle() : null;
        $record->formToken = $formToken;
        $record->save(false);
    }

    /**
     * Remove all unfinalized assets which are older than the TTL
     * specified in settings.
     */
    public function cleanUpUnfinalizedAssets(int $ageInMinutes): int
    {
        if (!\Craft::$app->db->tableExists(UnfinalizedFileRecord::TABLE)) {
            return 0;
        }

        if ($ageInMinutes <= 0) {
            return 0;
        }

        $mutex = \Craft::$app->getMutex();

        $deletedAssets = 0;
        if ($mutex->acquire(self::CLEANUP_CACHE_KEY, 30)) {
            try {
                $date = new \DateTime("-{$ageInMinutes} minutes");
                $date->setTimezone(new \DateTimeZone('UTC'));

                $assetIds = (new Query())
                    ->select(['assetId'])
                    ->from(UnfinalizedFileRecord::TABLE)
                    ->where(['<', '{{%freeform_unfinalized_files}}.[[dateCreated]]', Db::prepareDateForDb($date)])
                    ->column()
                ;

                $query = (new Query())
                    ->select(['assetId'])
                    ->from(UnfinalizedFileRecord::TABLE)
                    ->where(['<', '{{%freeform_unfinalized_files}}.[[dateCreated]]', Db::prepareDateForDb($date)])
                    ->getRawSql()
                ;

                foreach ($assetIds as $assetId) {
                    try {
                        $isDeleted = \Craft::$app
                            ->getElements()
                            ->deleteElementById($assetId, Asset::class, hardDelete: true)
                        ;

                        if ($isDeleted) {
                            ++$deletedAssets;
                        }
                    } catch (\Exception) {
                    }
                }
            } finally {
                $mutex->release(self::CLEANUP_CACHE_KEY);
            }
        }

        return $deletedAssets;
    }

    /**
     * Get a serializable array of asset sources containing
     * their ID, name and type.
     */
    public function getAssetSources(): array
    {
        /** @var Volume[] $volumes */
        $volumes = \Craft::$app->volumes->getAllVolumes();
        $assetSources = [];
        foreach ($volumes as $source) {
            $assetSources[] = [
                'id' => (int) $source->id,
                'name' => $source->name,
                'type' => 'volume',
            ];
        }

        return $assetSources;
    }

    public function getAssetUrlsFromIds(array $ids): array
    {
        $urls = [];
        foreach ($ids as $id) {
            if ($id && is_numeric($id)) {
                $asset = \Craft::$app->assets->getAssetById($id);
                if ($asset) {
                    $urls[] = $asset->getUrl();
                }
            }
        }

        return $urls;
    }

    public function getAssetMetadataFromIds(array $ids): array
    {
        $metadata = [];
        foreach ($ids as $id) {
            if ($id && is_numeric($id)) {
                $asset = \Craft::$app->assets->getAssetById($id);
                if ($asset) {
                    $data = [
                        'id' => $asset->id,
                        'filename' => $asset->filename,
                        'url' => $asset->getUrl(),
                        'size' => $asset->size,
                        'kind' => $asset->kind,
                    ];

                    if ('image' === $asset->kind) {
                        $data['dimensions'] = [
                            'width' => $asset->width,
                            'height' => $asset->height,
                        ];
                    }

                    $metadata[] = $data;
                }
            }
        }

        return $metadata;
    }

    /**
     * Get a key-value list of asset sources, indexed by ID.
     */
    public function getAssetSourceList(): array
    {
        /** @var Volume[] $volumes */
        $volumes = \Craft::$app->volumes->getAllVolumes();
        $assetSources = [];
        foreach ($volumes as $source) {
            $assetSources[(int) $source->id] = $source->name;
        }

        return $assetSources;
    }

    /**
     * Returns an array of all file kinds
     * [type => [ext, ext, ..]
     * I.e. ["image" => ["gif", "png", "jpg", "jpeg", ..]].
     */
    public function getFileKinds(): array
    {
        $fileKinds = Assets::getAllowedFileKinds();

        $returnArray = [];
        foreach ($fileKinds as $kind => $extensions) {
            $returnArray[$kind] = $extensions['extensions'];
        }

        return $returnArray;
    }

    /**
     * Returns an array of all valid file extensions for this field.
     */
    public function getValidExtensions(FileUploadField $field): array
    {
        $allFileKinds = $this->getFileKinds();
        $selectedFileKinds = $field->getFileKinds();

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

    private function getFolder($volumeId, string $subpath, Form $form)
    {
        $assetsService = \Craft::$app->getAssets();

        if (null === $volumeId || ($rootFolder = $assetsService->getRootFolderByVolumeId($volumeId)) === null) {
            throw new InvalidFsException();
        }

        $subpath = \is_string($subpath) ? trim($subpath, '/') : '';

        if ('' === $subpath) {
            $folder = $rootFolder;
        } else {
            try {
                $renderedSubpath = \Craft::$app->view->renderString($subpath, ['form' => $form]);
            } catch (Throwable $e) {
                throw new InvalidSubpathException($subpath, null, 0, $e);
            }

            if (
                '' === $renderedSubpath
                || trim($renderedSubpath, '/') != $renderedSubpath
                || str_contains($renderedSubpath, '//')
            ) {
                throw new InvalidSubpathException($subpath);
            }

            $segments = explode('/', $renderedSubpath);

            foreach ($segments as &$segment) {
                $segment = FileHelper::sanitizeFilename($segment, [
                    'asciiOnly' => \Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii,
                ]);
            }

            unset($segment);
            $subpath = implode('/', $segments);

            $folder = $assetsService->findFolder([
                'volumeId' => $volumeId,
                'path' => $subpath.'/',
            ]);

            // Ensure that the folder exists
            if (!$folder) {
                $volume = \Craft::$app->getVolumes()->getVolumeById($volumeId);
                $folder = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
            }
        }

        return $folder;
    }
}
