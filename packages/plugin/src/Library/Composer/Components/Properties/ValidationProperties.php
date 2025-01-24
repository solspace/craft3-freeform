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

class ValidationProperties extends AbstractProperties
{
    public const VALIDATION_TYPE_SUBMIT = 'submit';
    public const VALIDATION_TYPE_LIVE = 'live';

    public const DEFAULT_SUCCESS_MESSAGE = 'Form has been submitted successfully!';
    public const DEFAULT_ERROR_MESSAGE = 'Sorry, there was an error submitting the form. Please try again.';

    /** @var string */
    protected $validationType;

    /** @var string */
    protected $successMessage;

    /** @var string */
    protected $errorMessage;

    /** @var bool */
    protected $showSpinner;

    /** @var bool */
    protected $showLoadingText;

    /** @var string */
    protected $loadingText;

    /** @var string */
    protected $limitFormSubmissions;

    public function getValidationType(): string
    {
        return $this->validationType ?? self::VALIDATION_TYPE_SUBMIT;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage ?: self::DEFAULT_SUCCESS_MESSAGE;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage ?: self::DEFAULT_ERROR_MESSAGE;
    }

    public function isShowSpinner(): bool
    {
        return (bool) $this->showSpinner;
    }

    public function isShowLoadingText(): bool
    {
        return (bool) $this->showLoadingText;
    }

    public function getLoadingText()
    {
        return $this->loadingText ?? 'Loading...';
    }

    /**
     * @return null|string
     */
    public function getLimitFormSubmissions()
    {
        return $this->limitFormSubmissions;
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
            'validationType' => self::TYPE_STRING,
            'successMessage' => self::TYPE_STRING,
            'errorMessage' => self::TYPE_STRING,
            'showSpinner' => self::TYPE_BOOLEAN,
            'showLoadingText' => self::TYPE_BOOLEAN,
            'loadingText' => self::TYPE_STRING,
            'limitFormSubmissions' => self::TYPE_STRING,
        ];
    }
}
