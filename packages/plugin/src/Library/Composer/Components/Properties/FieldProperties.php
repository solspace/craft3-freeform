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

namespace Solspace\Freeform\Library\Composer\Components\Properties;

use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;

class FieldProperties extends AbstractProperties
{
    /** @var string */
    protected $hash;

    /** @var int */
    protected $id;

    /** @var string */
    protected $handle;

    /** @var string */
    protected $label;

    /** @var bool */
    protected $required;

    /** @var string */
    protected $placeholder;

    /** @var string */
    protected $instructions;

    /** @var string */
    protected $value;

    /** @var array */
    protected $values;

    /** @var array */
    protected $options;

    /** @var bool */
    protected $checked;

    /** @var bool */
    protected $showAsRadio;

    /** @var bool */
    protected $showAsCheckboxes;

    /** @var int */
    protected $notificationId;

    /** @var int */
    protected $assetSourceId;

    /** @var int */
    protected $integrationId;

    /** @var string */
    protected $resourceId;

    /** @var string */
    protected $emailFieldHash;

    /** @var string */
    protected $position;

    /** @var string */
    protected $labelNext;

    /** @var string */
    protected $labelPrev;

    /** @var bool */
    protected $disablePrev;

    /** @var array */
    protected $mapping;

    /** @var array */
    protected $fileKinds;

    /** @var int */
    protected $maxFileSizeKB;

    /** @var string */
    protected $defaultUploadLocation;

    /** @var int */
    protected $fileCount;

    /** @var int */
    protected $rows;

    /** @var string */
    protected $dateTimeType;

    /** @var bool */
    protected $generatePlaceholder;

    /** @var string */
    protected $dateOrder;

    /** @var bool */
    protected $date4DigitYear;

    /** @var bool */
    protected $dateLeadingZero;

    /** @var string */
    protected $dateSeparator;

    /** @var bool */
    protected $clock24h;

    /** @var string */
    protected $clockSeparator;

    /** @var bool */
    protected $clockAMPMSeparate;

    /** @var bool */
    protected $useDatepicker;

    /** @var string */
    protected $minDate;

    /** @var string */
    protected $maxDate;

    /** @var string */
    protected $locale;

    /** @var string */
    protected $initialValue;

    /** @var int */
    protected $minValue;

    /** @var int */
    protected $maxValue;

    /** @var int */
    protected $minLength;

    /** @var int */
    protected $maxLength;

    /** @var int */
    protected $decimalCount;

    /** @var string */
    protected $decimalSeparator;

    /** @var string */
    protected $thousandsSeparator;

    /** @var bool */
    protected $allowNegative;

    /** @var float */
    protected $step;

    /** @var string */
    protected $pattern;

    /** @var string */
    protected $targetFieldHash;

    /** @var string */
    protected $message;

    /** @var string */
    protected $colorIdle;

    /** @var string */
    protected $colorHover;

    /** @var string */
    protected $colorSelected;

    /** @var string */
    protected $source;

    /** @var string */
    protected $target;

    /** @var array */
    protected $configuration;

    /** @var string */
    protected $layout;

    /** @var string */
    protected $paymentType;

    /** @var float */
    protected $amount;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $interval;

    /** @var string */
    protected $plan;

    /** @var array */
    protected $children;

    /** @var array */
    protected $paymentFieldMapping;

    /** @var array */
    protected $customerFieldMapping;

    /** @var bool */
    protected $useJsMask;

    /** @var bool */
    protected $hidden;

    /** @var bool */
    protected $oneLine;

    /** @var array */
    protected $inputAttributes;

    /** @var array */
    protected $labelAttributes;

    /** @var array */
    protected $errorAttributes;

    /** @var array */
    protected $instructionAttributes;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var bool */
    protected $showClearButton;

    /** @var string */
    protected $borderColor;

    /** @var string */
    protected $backgroundColor;

    /** @var string */
    protected $penColor;

    /** @var float */
    protected $penDotSize;

    /** @var array */
    protected $tableLayout;

    /** @var int */
    protected $maxRows;

    /** @var bool */
    protected $useScript;

    /** @var array */
    protected $scales;

    /** @var array */
    protected $legends;

    /** @var bool */
    protected $twig;

    /** @var string */
    protected $accent;

    /** @var string */
    protected $theme;

    /** @var string */
    protected $url;

    /**
     * @return null|string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return null|bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return null|string
     */
    public function getValue()
    {
        return null !== $this->value ? (string) $this->value : null;
    }

    /**
     * @return null|array
     */
    public function getValues()
    {
        if (null === $this->values) {
            return null;
        }

        $values = $this->values;
        array_walk($values, function (&$value) {
            $value = (string) $value;
        });

        return $values;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        $return = [];
        if (\is_array($this->options)) {
            foreach ($this->options as $option) {
                $isChecked = false;
                if (null !== $this->getValue()) {
                    $isChecked = (string) $option['value'] === (string) $this->getValue();
                } elseif (null !== $this->getValues()) {
                    $isChecked = \in_array($option['value'], $this->getValues(), true);
                }

                $return[] = new Option((string) $option['label'], (string) $option['value'], $isChecked);
            }
        }

        return $return;
    }

    public function isChecked(): bool
    {
        return (bool) $this->checked;
    }

    /**
     * @return null|bool
     */
    public function isShowAsRadio()
    {
        return $this->showAsRadio;
    }

    /**
     * @return null|bool
     */
    public function isShowAsCheckboxes()
    {
        return $this->showAsCheckboxes;
    }

    /**
     * @return null|string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @return null|string
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * @return null|int
     */
    public function getNotificationId()
    {
        return $this->notificationId;
    }

    /**
     * @return null|int
     */
    public function getAssetSourceId()
    {
        return $this->assetSourceId;
    }

    /**
     * @return null|int
     */
    public function getIntegrationId()
    {
        return (int) $this->integrationId;
    }

    public function getResourceId(): string
    {
        return (string) $this->resourceId;
    }

    public function getEmailFieldHash(): string
    {
        return (string) $this->emailFieldHash;
    }

    /**
     * @return null|string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return null|string
     */
    public function getLabelNext()
    {
        return $this->labelNext;
    }

    /**
     * @return null|string
     */
    public function getLabelPrev()
    {
        return $this->labelPrev;
    }

    /**
     * @return null|bool
     */
    public function isDisablePrev()
    {
        return $this->disablePrev;
    }

    /**
     * @return null|array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return null|array
     */
    public function getFileKinds()
    {
        return $this->fileKinds;
    }

    /**
     * @return null|int
     */
    public function getMaxFileSizeKB()
    {
        return $this->maxFileSizeKB;
    }

    /**
     * @return null|int
     */
    public function getFileCount()
    {
        return $this->fileCount;
    }

    /**
     * @return null|int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @return null|string
     */
    public function getDateTimeType()
    {
        return $this->dateTimeType;
    }

    /**
     * @return null|bool
     */
    public function isGeneratePlaceholder()
    {
        return $this->generatePlaceholder;
    }

    /**
     * @return null|string
     */
    public function getDateOrder()
    {
        return $this->dateOrder;
    }

    /**
     * @return null|bool
     */
    public function isDate4DigitYear()
    {
        return $this->date4DigitYear;
    }

    /**
     * @return null|bool
     */
    public function isDateLeadingZero()
    {
        return $this->dateLeadingZero;
    }

    /**
     * @return null|string
     */
    public function getDateSeparator()
    {
        return $this->dateSeparator;
    }

    /**
     * @return null|bool
     */
    public function isClock24h()
    {
        return $this->clock24h;
    }

    /**
     * @return null|string
     */
    public function getClockSeparator()
    {
        return $this->clockSeparator;
    }

    /**
     * @return null|string
     */
    public function getDefaultUploadLocation()
    {
        return $this->defaultUploadLocation;
    }

    /**
     * @return null|bool
     */
    public function isClockAMPMSeparate()
    {
        return $this->clockAMPMSeparate;
    }

    /**
     * @return null|bool
     */
    public function isUseDatepicker()
    {
        return $this->useDatepicker;
    }

    /**
     * @return null|string
     */
    public function getMinDate()
    {
        return $this->minDate;
    }

    /**
     * @return null|string
     */
    public function getMaxDate()
    {
        return $this->maxDate;
    }

    /**
     * @return null|string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return null|string
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * @return null|int
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @return null|int
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @return null|int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @return null|int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return null|int
     */
    public function getDecimalCount()
    {
        return $this->decimalCount;
    }

    /**
     * @return null|float
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return null|string
     */
    public function getDecimalSeparator()
    {
        return $this->decimalSeparator;
    }

    /**
     * @return null|string
     */
    public function getThousandsSeparator()
    {
        return $this->thousandsSeparator;
    }

    /**
     * @return null|bool
     */
    public function isAllowNegative()
    {
        return $this->allowNegative;
    }

    /**
     * @return null|string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return null|int
     */
    public function getTargetFieldHash()
    {
        return $this->targetFieldHash;
    }

    /**
     * @return null|string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return null|string
     */
    public function getColorIdle()
    {
        return $this->colorIdle;
    }

    /**
     * @return null|string
     */
    public function getColorHover()
    {
        return $this->colorHover;
    }

    /**
     * @return null|string
     */
    public function getColorSelected()
    {
        return $this->colorSelected;
    }

    /**
     * @return null|string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return null|string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

    /**
     * @return null|float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getPlan()
    {
        return $this->plan;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function getPaymentFieldMapping()
    {
        return $this->paymentFieldMapping;
    }

    /**
     * @return array
     */
    public function getCustomerFieldMapping()
    {
        return $this->customerFieldMapping;
    }

    // @return bool|null
    public function isUseJsMask()
    {
        return $this->useJsMask;
    }

    /**
     * @return null|bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @return null|bool
     */
    public function isOneLine()
    {
        return $this->oneLine;
    }

    /**
     * @return null|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return null|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return null|array
     */
    public function getTableLayout()
    {
        return $this->tableLayout;
    }

    /**
     * @return null|int
     */
    public function getMaxRows()
    {
        return $this->maxRows;
    }

    /**
     * @return null|bool
     */
    public function isUseScript()
    {
        return $this->useScript;
    }

    /**
     * @return bool
     */
    public function isShowClearButton()
    {
        return $this->showClearButton;
    }

    /**
     * @return null|string
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * @return null|string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @return null|string
     */
    public function getPenColor()
    {
        return $this->penColor;
    }

    /**
     * @return null|float
     */
    public function getPenDotSize()
    {
        return $this->penDotSize;
    }

    public function getInputAttributes(): array
    {
        return $this->inputAttributes ?? [];
    }

    public function getLabelAttributes(): array
    {
        return $this->labelAttributes ?? [];
    }

    public function getErrorAttributes(): array
    {
        return $this->errorAttributes ?? [];
    }

    public function getInstructionAttributes(): array
    {
        return $this->instructionAttributes ?? [];
    }

    public function getScales(): array
    {
        return $this->scales ?? [];
    }

    public function getLegends(): array
    {
        return $this->legends ?? [];
    }

    public function isTwig(): bool
    {
        return (bool) $this->twig;
    }

    public function getAccent()
    {
        return $this->accent;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Return a list of all property fields and their type.
     *
     * [propertyKey => propertyType, ..]
     * E.g. ["name" => "string", ..]
     */
    protected function getPropertyManifest(): array
    {
        return [
            'hash' => self::TYPE_STRING,
            'id' => self::TYPE_INTEGER,
            'handle' => self::TYPE_STRING,
            'label' => self::TYPE_STRING,
            'required' => self::TYPE_BOOLEAN,
            'placeholder' => self::TYPE_STRING,
            'instructions' => self::TYPE_STRING,
            'value' => self::TYPE_STRING,
            'values' => self::TYPE_ARRAY,
            'options' => self::TYPE_ARRAY,
            'checked' => self::TYPE_BOOLEAN,
            'showAsRadio' => self::TYPE_BOOLEAN,
            'showAsCheckboxes' => self::TYPE_BOOLEAN,
            'notificationId' => self::TYPE_STRING,
            'assetSourceId' => self::TYPE_INTEGER,
            'integrationId' => self::TYPE_INTEGER,
            'resourceId' => self::TYPE_STRING,
            'emailFieldHash' => self::TYPE_STRING,
            'position' => self::TYPE_STRING,
            'labelNext' => self::TYPE_STRING,
            'labelPrev' => self::TYPE_STRING,
            'disablePrev' => self::TYPE_BOOLEAN,
            'mapping' => self::TYPE_ARRAY,
            'fileKinds' => self::TYPE_ARRAY,
            'maxFileSizeKB' => self::TYPE_INTEGER,
            'defaultUploadLocation' => self::TYPE_STRING,
            'fileCount' => self::TYPE_INTEGER,
            'rows' => self::TYPE_INTEGER,
            'dateTimeType' => self::TYPE_STRING,
            'generatePlaceholder' => self::TYPE_BOOLEAN,
            'dateOrder' => self::TYPE_STRING,
            'date4DigitYear' => self::TYPE_BOOLEAN,
            'dateLeadingZero' => self::TYPE_BOOLEAN,
            'dateSeparator' => self::TYPE_STRING,
            'clock24h' => self::TYPE_BOOLEAN,
            'clockSeparator' => self::TYPE_STRING,
            'clockAMPMSeparate' => self::TYPE_BOOLEAN,
            'useDatepicker' => self::TYPE_BOOLEAN,
            'minDate' => self::TYPE_STRING,
            'maxDate' => self::TYPE_STRING,
            'locale' => self::TYPE_STRING,
            'initialValue' => self::TYPE_STRING,
            'minValue' => self::TYPE_INTEGER,
            'maxValue' => self::TYPE_INTEGER,
            'minLength' => self::TYPE_INTEGER,
            'maxLength' => self::TYPE_INTEGER,
            'decimalCount' => self::TYPE_INTEGER,
            'decimalSeparator' => self::TYPE_STRING,
            'thousandsSeparator' => self::TYPE_STRING,
            'allowNegative' => self::TYPE_BOOLEAN,
            'step' => self::TYPE_DOUBLE,
            'pattern' => self::TYPE_STRING,
            'targetFieldHash' => self::TYPE_STRING,
            'message' => self::TYPE_STRING,
            'colorIdle' => self::TYPE_STRING,
            'colorHover' => self::TYPE_STRING,
            'colorSelected' => self::TYPE_STRING,
            'source' => self::TYPE_STRING,
            'target' => self::TYPE_STRING,
            'configuration' => self::TYPE_ARRAY,
            'layout' => self::TYPE_STRING,
            'paymentType' => self::TYPE_STRING,
            'amount' => self::TYPE_DOUBLE,
            'currency' => self::TYPE_STRING,
            'interval' => self::TYPE_STRING,
            'plan' => self::TYPE_STRING,
            'children' => self::TYPE_ARRAY,
            'paymentFieldMapping' => self::TYPE_ARRAY,
            'customerFieldMapping' => self::TYPE_ARRAY,
            'useJsMask' => self::TYPE_BOOLEAN,
            'hidden' => self::TYPE_BOOLEAN,
            'oneLine' => self::TYPE_BOOLEAN,
            'inputAttributes' => self::TYPE_ARRAY,
            'labelAttributes' => self::TYPE_ARRAY,
            'errorAttributes' => self::TYPE_ARRAY,
            'instructionAttributes' => self::TYPE_ARRAY,
            'tagAttributes' => self::TYPE_ARRAY,
            'scales' => self::TYPE_ARRAY,
            'legends' => self::TYPE_ARRAY,
            'width' => self::TYPE_INTEGER,
            'height' => self::TYPE_INTEGER,
            'showClearButton' => self::TYPE_BOOLEAN,
            'borderColor' => self::TYPE_STRING,
            'backgroundColor' => self::TYPE_STRING,
            'penColor' => self::TYPE_STRING,
            'penDotSize' => self::TYPE_DOUBLE,
            'tableLayout' => self::TYPE_ARRAY,
            'maxRows' => self::TYPE_INTEGER,
            'useScript' => self::TYPE_BOOLEAN,
            'twig' => self::TYPE_BOOLEAN,
            'accent' => self::TYPE_STRING,
            'theme' => self::TYPE_STRING,
            'url' => self::TYPE_STRING,
        ];
    }
}
