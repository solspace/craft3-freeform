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

class FormProperties extends AbstractProperties
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $handle;

    /** @var string */
    protected $formType;

    /** @var string */
    protected $color;

    /** @var string */
    protected $submissionTitleFormat;

    /** @var string */
    protected $description;

    /** @var string */
    protected $returnUrl;

    /** @var string */
    protected $extraPostUrl;

    /** @var string */
    protected $extraPostTriggerPhrase;

    /** @var bool */
    protected $storeData;

    /** @var bool */
    protected $ipCollectingEnabled;

    /** @var int */
    protected $defaultStatus;

    /** @var string */
    protected $formTemplate;

    /** @var string */
    protected $optInDataStorageTargetHash;

    /** @var array */
    protected $tagAttributes;

    /** @var bool */
    protected $ajaxEnabled;

    /** @var bool */
    protected $recaptchaEnabled = true;

    /** @var bool */
    protected $gtmEnabled = false;

    /** @var string */
    protected $gtmId;

    /** @var string */
    protected $gtmEventName;

    public function getName(): string
    {
        return $this->name;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getSubmissionTitleFormat(): string
    {
        return $this->submissionTitleFormat;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReturnUrl(): string
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

    public function isStoreData(): bool
    {
        return null !== $this->storeData ? (bool) $this->storeData : true;
    }

    public function isIpCollectingEnabled(): bool
    {
        return null !== $this->ipCollectingEnabled ? (bool) $this->ipCollectingEnabled : true;
    }

    public function getDefaultStatus(): int
    {
        return $this->defaultStatus;
    }

    public function getFormTemplate(): string
    {
        return $this->formTemplate;
    }

    /**
     * @return null|string
     */
    public function getOptInDataStorageTargetHash()
    {
        return $this->optInDataStorageTargetHash ?: null;
    }

    public function getTagAttributes(): array
    {
        return $this->tagAttributes ?? [];
    }

    public function isAjaxEnabled(): bool
    {
        return (bool) $this->ajaxEnabled;
    }

    public function isRecaptchaEnabled(): bool
    {
        return (bool) $this->recaptchaEnabled;
    }

    public function isGtmEnabled(): bool
    {
        return (bool) $this->gtmEnabled;
    }

    public function getGtmId(): string
    {
        return $this->gtmId ?? '';
    }

    public function getGtmEventName(): string
    {
        return $this->gtmEventName ?? '';
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
            'name' => self::TYPE_STRING,
            'handle' => self::TYPE_STRING,
            'formType' => self::TYPE_STRING,
            'color' => self::TYPE_STRING,
            'submissionTitleFormat' => self::TYPE_STRING,
            'description' => self::TYPE_STRING,
            'returnUrl' => self::TYPE_STRING,
            'extraPostUrl' => self::TYPE_STRING,
            'extraPostTriggerPhrase' => self::TYPE_STRING,
            'storeData' => self::TYPE_BOOLEAN,
            'ipCollectingEnabled' => self::TYPE_BOOLEAN,
            'defaultStatus' => self::TYPE_INTEGER,
            'formTemplate' => self::TYPE_STRING,
            'optInDataStorageTargetHash' => self::TYPE_STRING,
            'tagAttributes' => self::TYPE_ARRAY,
            'ajaxEnabled' => self::TYPE_BOOLEAN,
            'recaptchaEnabled' => self::TYPE_BOOLEAN,
            'gtmEnabled' => self::TYPE_BOOLEAN,
            'gtmId' => self::TYPE_STRING,
            'gtmEventName' => self::TYPE_STRING,
        ];
    }
}
