<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use craft\helpers\UrlHelper;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\PlaceholderInterface;
use Solspace\Freeform\Freeform;

#[Type(
    name: 'File Drag & Drop',
    typeShorthand: 'file-dnd',
    iconPath: __DIR__.'/../Icons/file-upload-drag-drop.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/file-drag-n-drop.ejs',
)]
class FileDragAndDropField extends FileUploadField implements ExtraFieldInterface, PlaceholderInterface
{
    public const DEFAULT_ACCENT = '#3a85ee';
    public const DEFAULT_THEME = 'light';
    public const DEFAULT_PLACEHOLDER = 'Drag and drop files here or click to upload';

    #[Input\ColorPicker(
        label: 'Accent Color',
        instructions: 'Select accent color',
        order: 6,
    )]
    protected string $accent = self::DEFAULT_ACCENT;

    #[Input\Select(
        label: 'Theme',
        instructions: 'Select theme',
        order: 7,
        options: [
            'light' => 'Light',
            'dark' => 'Dark',
        ],
    )]
    protected string $theme = self::DEFAULT_THEME;

    #[Input\Text(
        instructions: 'Field placeholder.',
        order: 8,
    )]
    protected string $placeholder = self::DEFAULT_PLACEHOLDER;

    public function getAccent(): string
    {
        return $this->accent;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getType(): string
    {
        return self::TYPE_FILE_DRAG_AND_DROP;
    }

    public function getInputHtml(): string
    {
        $messageFiles = Freeform::t(
            'Maximum file upload limit of {limit} reached',
            ['limit' => $this->getFileCount()]
        );
        $messageSize = Freeform::t(
            'Maximum file upload size is {maxFileSize}KB',
            ['maxFileSize' => $this->getMaxFileSizeKB()]
        );

        $fileCount = 0;
        if (is_countable($this->getValue())) {
            $fileCount = \count($this->getValue());
        }

        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->append('class', 'freeform-file-dnd__input')
            ->replace('data-freeform-file-upload', $this->getHandle())
            ->setIfEmpty('data-error-append-target', $this->getHandle())
            ->replace('data-file-count', $fileCount)
            ->replace('data-max-files', $this->getFileCount())
            ->replace('data-max-size', $this->getMaxFileSizeBytes())
            ->setIfEmpty('data-theme', $this->getTheme())
            ->setIfEmpty('data-message-progress', Freeform::t('Upload in progress...'))
            ->setIfEmpty('data-message-complete', Freeform::t('Upload complete!'))
            ->setIfEmpty('data-message-files', $messageFiles)
            ->setIfEmpty('data-message-size', $messageSize)
            ->setIfEmpty('data-accent', $this->getAccent())
            ->setIfEmpty('data-base-url', UrlHelper::siteUrl('/freeform'))
        ;

        $output = '';
        $output .= '<div'.$attributes.'>';
        $output .= '<div data-placeholder class="freeform-file-dnd__placeholder">';
        $output .= $this->translate('placeholder', $this->getPlaceholder());
        $output .= '</div>';
        $output .= '<div data-preview-zone class="freeform-file-dnd__preview-zone"></div>';
        $output .= '<ul data-messages class="freeform-file-dnd__messages"></ul>';
        $output .= '<input type="file" id="form-input-'.$this->getHandle().'" multiple />';
        $output .= '</div>';

        return $output;
    }

    public function uploadFile(): ?array
    {
        return null;
    }

    public function includeInGqlSchema(): bool
    {
        return false;
    }
}
