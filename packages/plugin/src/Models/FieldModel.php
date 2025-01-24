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

namespace Solspace\Freeform\Models;

use craft\base\Model;
use Solspace\Freeform\Fields\FileUploadField;
use Solspace\Freeform\Fields\Pro\FileDragAndDropField;
use Solspace\Freeform\Fields\Pro\SignatureField;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\ExternalOptionsInterface;
use Solspace\Freeform\Library\Helpers\HashHelper;

class FieldModel extends Model implements \JsonSerializable
{
    public const SMALL_DATA_STORAGE_LENGTH = 100;

    public const PROPERTY_TYPE_BOOL = 'bool';
    public const PROPERTY_TYPE_INT = 'int';

    /** @var int */
    public $id;

    /** @var string */
    public $type;

    /** @var string */
    public $handle;

    /** @var string */
    public $label;

    /** @var bool */
    public $required;

    /** @var string */
    public $instructions;

    /** @var array */
    public $metaProperties;

    public static function create(): self
    {
        $field = new self();
        $field->type = AbstractField::TYPE_TEXT;
        $field->required = false;

        return $field;
    }

    public function getHash(): string
    {
        return HashHelper::hash($this->id);
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return null|mixed
     */
    public function getMetaProperty($name, $defaultValue = null)
    {
        if (\is_array($this->metaProperties) && isset($this->metaProperties[$name])) {
            $value = $this->metaProperties[$name];

            if (null === $value) {
                return $defaultValue;
            }

            return $this->parseMetaProperty($name, $value);
        }

        return $defaultValue;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function setMetaProperty($name, $value): self
    {
        $properties = $this->metaProperties ?? [];

        if (null === $value && isset($properties[$name])) {
            unset($properties[$name]);
        } elseif (null !== $value) {
            $properties[$name] = $this->parseMetaProperty($name, $value);
        }

        $this->metaProperties = $properties;

        return $this;
    }

    /**
     * @return $this
     */
    public function addMetaProperties(array $properties): self
    {
        $metaProperties = $this->metaProperties ?? [];
        $metaProperties = array_merge($metaProperties, $properties);

        $this->metaProperties = $metaProperties;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $returnArray = [
            'id' => (int) $this->id,
            'hash' => $this->getHash(),
            'type' => $this->type,
            'handle' => $this->handle,
            'label' => $this->label,
            'required' => (bool) $this->required,
            'instructions' => $this->instructions ?? '',
        ];

        if (\in_array(
            $this->type,
            [
                FieldInterface::TYPE_TEXT,
                FieldInterface::TYPE_TEXTAREA,
                FieldInterface::TYPE_HIDDEN,
            ],
            true
        )) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
        }

        if (FieldInterface::TYPE_TEXTAREA === $this->type) {
            $returnArray['rows'] = $this->getMetaProperty('rows', 2);
        }

        if (FieldInterface::TYPE_CHECKBOX === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', 'Yes');
            $returnArray['checked'] = $this->getMetaProperty('checked', false);
        }

        if (FieldInterface::TYPE_EMAIL === $this->type) {
            $returnArray['notificationId'] = $this->getMetaProperty('notificationId', 0);
            $returnArray['values'] = $this->getMetaProperty('values', []);
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
        }

        if (FieldInterface::TYPE_DYNAMIC_RECIPIENTS === $this->type) {
            $returnArray['notificationId'] = $this->getMetaProperty('notificationId', 0);
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['options'] = $this->getMetaProperty('options', []);
            $returnArray['showAsRadio'] = $this->getMetaProperty('showAsRadio', false);
            $returnArray['showAsCheckboxes'] = $this->getMetaProperty('showAsCheckboxes', false);
        }

        if (FieldInterface::TYPE_CHECKBOX_GROUP === $this->type) {
            $returnArray['showCustomValues'] = $this->hasCustomOptionValues();
            $returnArray['values'] = $this->getMetaProperty('values', []);
            $returnArray['options'] = $this->getMetaProperty('options', []);
            $returnArray['source'] = $this->getMetaProperty('source', ExternalOptionsInterface::SOURCE_CUSTOM);
            $returnArray['target'] = $this->getMetaProperty('target', null);
            $returnArray['configuration'] = $this->getMetaProperty('target', null);
        }

        if (FieldInterface::TYPE_MULTIPLE_SELECT === $this->type) {
            $returnArray['showCustomValues'] = $this->hasCustomOptionValues();
            $returnArray['values'] = $this->getMetaProperty('values', []);
            $returnArray['options'] = $this->getMetaProperty('options', []);
            $returnArray['source'] = $this->getMetaProperty('source', ExternalOptionsInterface::SOURCE_CUSTOM);
            $returnArray['target'] = $this->getMetaProperty('target', null);
            $returnArray['configuration'] = $this->getMetaProperty('target', null);
        }

        if (\in_array($this->type, [FieldInterface::TYPE_FILE, FieldInterface::TYPE_FILE_DRAG_AND_DROP], true)) {
            $returnArray['assetSourceId'] = (int) $this->getMetaProperty('assetSourceId', 0);
            $returnArray['maxFileSizeKB'] = (int) $this->getMetaProperty(
                'maxFileSizeKB',
                FileUploadField::DEFAULT_MAX_FILESIZE_KB
            );
            $returnArray['defaultUploadLocation'] = $this->getMetaProperty('defaultUploadLocation', '');
            $returnArray['fileCount'] = (int) $this->getMetaProperty('fileCount', FileUploadField::DEFAULT_FILE_COUNT);
            $returnArray['fileKinds'] = $this->getMetaProperty('fileKinds', ['image', 'pdf']);
        }

        if (FieldInterface::TYPE_FILE_DRAG_AND_DROP === $this->type) {
            $returnArray['theme'] = $this->getMetaProperty('theme', FileDragAndDropField::DEFAULT_THEME);
            $returnArray['accent'] = $this->getMetaProperty('accent', FileDragAndDropField::DEFAULT_ACCENT);
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', FileDragAndDropField::DEFAULT_PLACEHOLDER);

            if ($returnArray['accent'] && '#' !== substr($returnArray['accent'], 0, 1)) {
                $returnArray['accent'] = '#'.$returnArray['accent'];
            }
        }

        if (\in_array($this->type, [FieldInterface::TYPE_RADIO_GROUP, FieldInterface::TYPE_SELECT], true)) {
            $returnArray['showCustomValues'] = $this->hasCustomOptionValues();
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['options'] = $this->getMetaProperty('options', []);
            $returnArray['source'] = $this->getMetaProperty('source', ExternalOptionsInterface::SOURCE_CUSTOM);
            $returnArray['target'] = $this->getMetaProperty('target', null);
            $returnArray['configuration'] = $this->getMetaProperty('target', null);
        }

        if (FieldInterface::TYPE_DATETIME === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
            $returnArray['initialValue'] = $this->getMetaProperty('initialValue');
            $returnArray['dateTimeType'] = $this->getMetaProperty('dateTimeType', 'both');
            $returnArray['generatePlaceholder'] = $this->getMetaProperty('generatePlaceholder', true);
            $returnArray['dateOrder'] = $this->getMetaProperty('dateOrder', 'ymd');
            $returnArray['date4DigitYear'] = $this->getMetaProperty('date4DigitYear', true);
            $returnArray['dateLeadingZero'] = $this->getMetaProperty('dateLeadingZero', true);
            $returnArray['dateSeparator'] = $this->getMetaProperty('dateSeparator', '/');
            $returnArray['clock24h'] = $this->getMetaProperty('clock24h', false);
            $returnArray['clockSeparator'] = $this->getMetaProperty('clockSeparator', ':');
            $returnArray['clockAMPMSeparate'] = $this->getMetaProperty('clockAMPMSeparate', true);
            $returnArray['useDatepicker'] = $this->getMetaProperty('useDatepicker', true);
            $returnArray['minDate'] = $this->getMetaProperty('minDate', '');
            $returnArray['maxDate'] = $this->getMetaProperty('maxDate', '');
            $returnArray['locale'] = $this->getMetaProperty('locale', '');
        }

        if (FieldInterface::TYPE_NUMBER === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
            $returnArray['minLength'] = $this->getMetaProperty('minLength');
            $returnArray['maxLength'] = $this->getMetaProperty('maxLength');
            $returnArray['minValue'] = $this->getMetaProperty('minValue');
            $returnArray['maxValue'] = $this->getMetaProperty('maxValue');
            $returnArray['decimalCount'] = $this->getMetaProperty('decimalCount');
            $returnArray['decimalSeparator'] = $this->getMetaProperty('decimalSeparator', '.');
            $returnArray['thousandsSeparator'] = $this->getMetaProperty('thousandsSeparator', ',');
            $returnArray['allowNegative'] = $this->getMetaProperty('allowNegative', false);
            $returnArray['step'] = $this->getMetaProperty('step');
        }

        if (FieldInterface::TYPE_RATING === $this->type) {
            $returnArray['value'] = (int) $this->getMetaProperty('value', 0);
            $returnArray['maxValue'] = $this->getMetaProperty('maxValue', 5);
            $returnArray['colorIdle'] = $this->getMetaProperty('colorIdle', '#ddd');
            $returnArray['colorHover'] = $this->getMetaProperty('colorHover', 'gold');
            $returnArray['colorSelected'] = $this->getMetaProperty('colorSelected', '#f70');
        }

        if (FieldInterface::TYPE_REGEX === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
            $returnArray['pattern'] = $this->getMetaProperty('pattern');
            $returnArray['message'] = $this->getMetaProperty('message');
        }

        if (FieldInterface::TYPE_CONFIRMATION === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
            $returnArray['targetFieldId'] = $this->getMetaProperty('targetFieldId');
        }

        if (FieldInterface::TYPE_PHONE === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
            $returnArray['pattern'] = $this->getMetaProperty('pattern', '');
            $returnArray['useJsMask'] = $this->getMetaProperty('useJsMask', false);
        }

        if (FieldInterface::TYPE_WEBSITE === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['placeholder'] = $this->getMetaProperty('placeholder', '');
        }

        if (FieldInterface::TYPE_OPINION_SCALE === $this->type) {
            $returnArray['value'] = $this->getMetaProperty('value', '');
            $returnArray['scales'] = $this->getMetaProperty('scales', []);
            $returnArray['legends'] = $this->getMetaProperty('legends', []);
        }

        if (FieldInterface::TYPE_SIGNATURE === $this->type) {
            $returnArray['width'] = (int) $this->getMetaProperty('width', SignatureField::DEFAULT_WIDTH);
            $returnArray['height'] = (int) $this->getMetaProperty('height', SignatureField::DEFAULT_HEIGHT);
            $returnArray['showClearButton'] = (bool) $this->getMetaProperty('showClearButton', true);
            $returnArray['borderColor'] = $this->getMetaProperty('borderColor', SignatureField::DEFAULT_BORDER_COLOR);
            $returnArray['backgroundColor'] = $this->getMetaProperty('backgroundColor', SignatureField::DEFAULT_BACKGROUND_COLOR);
            $returnArray['penColor'] = $this->getMetaProperty('penColor', SignatureField::DEFAULT_PEN_COLOR);
            $returnArray['penDotSize'] = (float) $this->getMetaProperty('penDotSize', SignatureField::DEFAULT_PEN_DOT_SIZE);
        }

        if (FieldInterface::TYPE_TABLE === $this->type) {
            $returnArray['tableLayout'] = $this->getMetaProperty('tableLayout', []);
            $returnArray['maxRows'] = $this->getMetaProperty('maxRows', 0);
            $returnArray['useScript'] = $this->getMetaProperty('useScript', true);
        }

        if (FieldInterface::TYPE_HTML === $this->type) {
            $returnArray['twig'] = $this->getMetaProperty('twig', false);
        }

        if (FieldInterface::TYPE_SAVE === $this->type) {
            $returnArray['url'] = $this->getMetaProperty('url');
        }

        if (\in_array(
            $this->type,
            [FieldInterface::TYPE_HIDDEN, FieldInterface::TYPE_HTML, FieldInterface::TYPE_SUBMIT],
            true
        )) {
            unset($returnArray['instructions']);
        }

        return $returnArray;
    }

    /**
     * @param bool $forceLabelToValue
     */
    public function setPostValues(array $postValues, $forceLabelToValue = false)
    {
        /**
         * @var array $labels
         * @var array $values
         * @var array $checkedByDefault
         */
        $labels = $postValues['labels'];
        $values = $postValues['values'];
        $checkedByDefault = $postValues['checked'];

        $savableValue = null;
        $savableValues = [];
        $savableOptions = [];
        foreach ($labels as $index => $label) {
            $value = $values[$index];

            if (empty($label) && empty($value)) {
                continue;
            }

            $fieldValue = $value;
            if (empty($label)) {
                $fieldLabel = $value;
            } else {
                $fieldValue = $value;
                $fieldLabel = $label;
            }

            if ($forceLabelToValue) {
                $fieldValue = $fieldLabel;
            }

            $isChecked = (bool) $checkedByDefault[$index];
            if ($isChecked) {
                switch ($this->type) {
                    case FieldInterface::TYPE_CHECKBOX_GROUP:
                    case FieldInterface::TYPE_MULTIPLE_SELECT:
                        $savableValues[] = $fieldValue;

                        break;

                    case FieldInterface::TYPE_RADIO_GROUP:
                    case FieldInterface::TYPE_SELECT:
                    case FieldInterface::TYPE_DYNAMIC_RECIPIENTS:
                        $savableValue = $fieldValue;

                        break;
                }
            }

            $item = new \stdClass();
            $item->value = $fieldValue;
            $item->label = $fieldLabel;

            $savableOptions[] = $item;
        }

        $this->setMetaProperty('options', !empty($savableOptions) ? $savableOptions : null);
        $this->setMetaProperty('values', !empty($savableValues) ? $savableValues : null);
        $this->setMetaProperty('value', !empty($savableValue) ? $savableValue : null);
        $this->setMetaProperty('checked', null);
    }

    public function hasCustomOptionValues(): bool
    {
        /** @var array $options */
        $options = $this->getMetaProperty('options');
        if (empty($options)) {
            return false;
        }

        foreach ($options as $valueData) {
            if (\is_object($valueData)) {
                $valueData = (array) $valueData;
            }

            if ($valueData['value'] !== $valueData['label']) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function safeAttributes()
    {
        return [
            'id',
            'type',
            'label',
            'handle',
            'required',
            'instructions',
            'metaProperties',
        ];
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    private function parseMetaProperty($name, $value)
    {
        static $customTypes = [
            'useDatepicker' => self::PROPERTY_TYPE_BOOL,
            'checked' => self::PROPERTY_TYPE_BOOL,
            'showAsRadio' => self::PROPERTY_TYPE_BOOL,
            'showAsCheckboxes' => self::PROPERTY_TYPE_BOOL,
            'generatePlaceholder' => self::PROPERTY_TYPE_BOOL,
            'date4DigitYear' => self::PROPERTY_TYPE_BOOL,
            'dateLeadingZero' => self::PROPERTY_TYPE_BOOL,
            'clock24h' => self::PROPERTY_TYPE_BOOL,
            'clockLeadingZero' => self::PROPERTY_TYPE_BOOL,
            'clockAMPMSeparate' => self::PROPERTY_TYPE_BOOL,
            'allowNegative' => self::PROPERTY_TYPE_BOOL,
            'notificationId' => self::PROPERTY_TYPE_INT,
            'assetSourceId' => self::PROPERTY_TYPE_INT,
            'rows' => self::PROPERTY_TYPE_INT,
            'maxFileSizeKB' => self::PROPERTY_TYPE_INT,
            'fileCount' => self::PROPERTY_TYPE_INT,
            'minLength' => self::PROPERTY_TYPE_INT,
            'maxLength' => self::PROPERTY_TYPE_INT,
            'minValue' => self::PROPERTY_TYPE_INT,
            'maxValue' => self::PROPERTY_TYPE_INT,
            'twig' => self::PROPERTY_TYPE_BOOL,
        ];

        if (isset($customTypes[$name])) {
            switch ($customTypes[$name]) {
                case self::PROPERTY_TYPE_BOOL:
                    return (bool) $value;

                case self::PROPERTY_TYPE_INT:
                    return null !== $value ? (int) $value : null;
            }
        }

        return $value;
    }
}
