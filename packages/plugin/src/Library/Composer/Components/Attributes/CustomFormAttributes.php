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

class CustomFormAttributes extends AbstractAttributes
{
    /** @var string */
    protected $returnUrl;

    /** @var string */
    protected $extraPostUrl;

    /** @var string */
    protected $extraPostTriggerPhrase;

    /** @var string */
    protected $inputClass;

    /** @var string */
    protected $submitClass;

    /** @var string */
    protected $rowClass;

    /** @var string */
    protected $columnClass;

    /** @var string */
    protected $labelClass;

    /** @var string */
    protected $errorClass;

    /** @var array of strings */
    protected $class;

    /** @var string */
    protected $instructionsClass;

    /** @var bool */
    protected $instructionsBelowField;

    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $method;

    /** @var string */
    protected $action;

    /** @var bool */
    protected $useRequiredAttribute;

    /** @var array */
    protected $formAttributes;

    /** @var array */
    protected $inputAttributes;

    /** @var array */
    protected $dynamicNotification;

    /** @var string */
    protected $fieldIdPrefix;

    /** @var string */
    protected $status;

    /** @var int */
    protected $statusId;

    /** @var string */
    protected $submissionToken;

    /** @var string */
    protected $recaptchaAction;

    /** @var array */
    protected $suppress;

    /** @var array */
    protected $relations;

    /** @var string */
    protected $formattingTemplate;

    /** @var bool */
    protected $disableRecaptcha;

    /** @var bool */
    protected $gtmEnabled;

    /** @var string */
    protected $gtmId;

    /** @var string */
    protected $gtmEventName;

    /** @var int|string */
    protected $elementId;

    /**
     * @return null|string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @return null|string
     */
    public function getExtraPostUrl()
    {
        return $this->extraPostUrl;
    }

    /**
     * @return null|string
     */
    public function getExtraPostTriggerPhrase()
    {
        return $this->extraPostTriggerPhrase;
    }

    /**
     * @return null|string
     */
    public function getInputClass()
    {
        return $this->extractClassValue($this->inputClass);
    }

    /**
     * @return null|string
     */
    public function getSubmitClass()
    {
        return $this->extractClassValue($this->submitClass);
    }

    /**
     * @return null|string
     */
    public function getRowClass()
    {
        return $this->extractClassValue($this->rowClass);
    }

    /**
     * @return null|string
     */
    public function getColumnClass()
    {
        return $this->extractClassValue($this->columnClass);
    }

    /**
     * @return null|string
     */
    public function getLabelClass()
    {
        return $this->extractClassValue($this->labelClass);
    }

    /**
     * @return null|string
     */
    public function getErrorClass()
    {
        return $this->extractClassValue($this->errorClass);
    }

    /**
     * @return null|string
     */
    public function getClass()
    {
        return $this->extractClassValue($this->class);
    }

    /**
     * @return null|string
     */
    public function getInstructionsClass()
    {
        return $this->instructionsClass;
    }

    /**
     * @return null|bool
     */
    public function isInstructionsBelowField()
    {
        return $this->instructionsBelowField;
    }

    /**
     * @return null|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return null|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return null|bool
     */
    public function getUseRequiredAttribute()
    {
        return $this->useRequiredAttribute;
    }

    /**
     * @return null|array
     */
    public function getFormAttributes()
    {
        if (null === $this->formAttributes) {
            return $this->formAttributes;
        }

        if (!\is_array($this->formAttributes)) {
            return [$this->formAttributes];
        }

        return $this->formAttributes;
    }

    public function getFormAttributesAsString(): string
    {
        $formAttributes = $this->getFormAttributes() ?: [];

        return $this->getAttributeStringFromArray($formAttributes);
    }

    /**
     * @return null|array
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
     * @return null|string
     */
    public function getFieldIdPrefix()
    {
        return $this->fieldIdPrefix;
    }

    /**
     * @return null|string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return null|int
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @return null|string
     */
    public function getSubmissionToken()
    {
        return $this->submissionToken;
    }

    /**
     * @return null|string
     */
    public function getRecaptchaAction()
    {
        return $this->recaptchaAction;
    }

    /**
     * @return null|array
     */
    public function getSuppress()
    {
        return $this->suppress;
    }

    /**
     * @return null|array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @return null|string
     */
    public function getFormattingTemplate()
    {
        return $this->formattingTemplate;
    }

    /**
     * @return null|bool
     */
    public function isDisableRecaptcha()
    {
        return $this->disableRecaptcha;
    }

    /**
     * @return null|bool
     */
    public function isGtmEnabled()
    {
        return $this->gtmEnabled;
    }

    /**
     * @return null|string
     */
    public function getGtmId()
    {
        return $this->gtmId;
    }

    /**
     * @return null|string
     */
    public function getGtmEventName()
    {
        return $this->gtmEventName;
    }

    /**
     * @return int|string
     */
    public function getElementId()
    {
        return $this->elementId;
    }
}
