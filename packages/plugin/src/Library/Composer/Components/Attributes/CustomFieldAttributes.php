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

namespace Solspace\Freeform\Library\Composer\Components\Attributes;

use Solspace\Freeform\Form\Bags\PropertyBag;
use Solspace\Freeform\Library\Composer\Components\AbstractField;

class CustomFieldAttributes extends AbstractAttributes
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $class;

    /** @var string */
    protected $labelClass;

    /** @var string */
    protected $errorClass;

    /** @var string */
    protected $instructionsClass;

    /** @var bool */
    protected $instructionsBelowField;

    /** @var string */
    protected $placeholder;

    /** @var mixed */
    protected $overrideValue;

    /** @var bool */
    protected $useRequiredAttribute;

    /** @var array */
    protected $inputAttributes;

    /** @var string */
    protected $addButtonLabel;

    /** @var string */
    protected $addButtonClass;

    /** @var string */
    protected $removeButtonLabel;

    /** @var string */
    protected $removeButtonClass;

    /** @var string */
    protected $tableTextInputClass;

    /** @var string */
    protected $tableCheckboxInputClass;

    /** @var string */
    protected $tableSelectInputClass;

    /** @var string */
    protected $tableLabelsClass;

    /** @var AbstractField */
    private $field;

    /** @var PropertyBag */
    private $formProperties;

    /**
     * CustomFieldAttributes constructor.
     */
    public function __construct(
        AbstractField $field,
        array $attributes = null,
        PropertyBag $formPropertyBag = null
    ) {
        parent::__construct($attributes);

        $this->field = $field;
        $this->formProperties = $formPropertyBag;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        $value = $this->class;
        if (null !== $this->formProperties) {
            $value = $this->combineClassStrings($value, $this->formProperties->get('inputClass'));
        }

        return $this->extractClassValue($value);
    }

    /**
     * @return string
     */
    public function getInputClassOnly()
    {
        return $this->extractClassValue($this->class);
    }

    /**
     * @return string
     */
    public function getLabelClass()
    {
        $value = $this->labelClass;
        if (null !== $this->formProperties) {
            $value = $this->combineClassStrings($value, $this->formProperties->get('labelClass'));
        }

        return $this->extractClassValue($value);
    }

    /**
     * @return string
     */
    public function getErrorClass()
    {
        $value = $this->errorClass;
        if (null !== $this->formProperties) {
            $value = $this->combineClassStrings($value, $this->formProperties->get('errorClass'));
        }

        return $this->extractClassValue($value);
    }

    /**
     * @return string
     */
    public function getInstructionsClass()
    {
        $value = $this->instructionsClass;
        if (null !== $this->formProperties) {
            $value = $this->combineClassStrings($value, $this->formProperties->get('instructionsClass'));
        }

        return $this->extractClassValue($value);
    }

    /**
     * @return bool
     */
    public function isInstructionsBelowField()
    {
        $value = $this->instructionsBelowField;
        if (!$value && null !== $this->formProperties) {
            $value = $this->formProperties->get('instructionsBelowField');
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @return array
     */
    public function getInputAttributes()
    {
        if (null === $this->inputAttributes) {
            return $this->inputAttributes;
        }

        if (!\is_array($this->inputAttributes)) {
            return [$this->inputAttributes];
        }

        return $this->inputAttributes;
    }

    /**
     * @return string
     */
    public function getInputAttributesAsString()
    {
        $formInputAttributes = $this->formProperties->get('inputAttributes', []);
        $inputAttributes = $this->getInputAttributes();

        if ($formInputAttributes) {
            if ($inputAttributes) {
                $inputAttributes = array_merge($formInputAttributes, $inputAttributes);
            } else {
                $inputAttributes = $formInputAttributes;
            }
        }

        if (!\is_array($inputAttributes)) {
            $inputAttributes = [];
        }

        return $this->getAttributeStringFromArray($inputAttributes);
    }

    /**
     * @return bool
     */
    public function getUseRequiredAttribute()
    {
        $value = $this->useRequiredAttribute;

        if (null === $value && null !== $this->formProperties) {
            $value = $this->formProperties->get('useRequiredAttribute');
        }

        return $value;
    }

    /**
     * @return null|string
     */
    public function getFieldIdPrefix()
    {
        return $this->formProperties->get('fieldIdPrefix');
    }

    /**
     * @return null|string
     */
    public function getAddButtonLabel()
    {
        return $this->addButtonLabel;
    }

    /**
     * @return null|string
     */
    public function getAddButtonClass()
    {
        return $this->addButtonClass;
    }

    /**
     * @return null|string
     */
    public function getRemoveButtonLabel()
    {
        return $this->removeButtonLabel;
    }

    /**
     * @return null|string
     */
    public function getRemoveButtonClass()
    {
        return $this->removeButtonClass;
    }

    /**
     * @return null|string
     */
    public function getTableTextInputClass()
    {
        return $this->tableTextInputClass;
    }

    /**
     * @return null|string
     */
    public function getTableCheckboxInputClass()
    {
        return $this->tableCheckboxInputClass;
    }

    /**
     * @return null|string
     */
    public function getTableLabelsClass()
    {
        return $this->tableLabelsClass;
    }

    /**
     * @return null|string
     */
    public function getTableSelectInputClass()
    {
        return $this->tableSelectInputClass;
    }

    /**
     * Takes a two class strings, explodes them into arrays, merges, then returns a glued string.
     */
    private function combineClassStrings(string $classStringA = null, string $classStringB = null): string
    {
        $classListA = explode(' ', $classStringA ?: '');
        $classListB = explode(' ', $classStringB ?: '');

        $combined = array_merge($classListA, $classListB);
        $combined = array_unique($combined);
        $combined = array_filter($combined);

        return implode(' ', $combined);
    }
}
