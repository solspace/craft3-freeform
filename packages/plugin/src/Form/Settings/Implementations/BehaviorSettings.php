<?php

namespace Solspace\Freeform\Form\Settings\Implementations;

use Solspace\Freeform\Attributes\Form\SettingNamespace;
use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Implementations\Date\DateTimeTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\Translatable;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Attributes\Property\ValueGenerator;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Bundles\Form\Limiting\FormLimiting;
use Solspace\Freeform\Bundles\Form\SuccessBehavior\SuccessBehaviorOptionsGenerator;
use Solspace\Freeform\Form\Settings\Implementations\Options\FormLimitingOptions;
use Solspace\Freeform\Form\Settings\Implementations\Options\SuccessTemplateOptions;
use Solspace\Freeform\Form\Settings\Implementations\ValueGenerators\SuccessTemplateGenerator;
use Solspace\Freeform\Form\Settings\SettingsNamespace;

#[SettingNamespace(
    'Behavior',
    order: 2,
)]
class BehaviorSettings extends SettingsNamespace
{
    public const SUCCESS_BEHAVIOR_RELOAD = 'reload';
    public const SUCCESS_BEHAVIOR_REDIRECT_RETURN_URL = 'redirect-return-url';
    public const SUCCESS_BEHAVIOR_LOAD_SUCCESS_TEMPLATE = 'load-success-template';

    private const SECTION_SUCCESS_AND_ERRORS = 'success-and-errors';
    private const SECTION_PROCESSING = 'processing';
    private const SECTION_LIMITS = 'limits';

    private const DEFAULT_SUCCESS_MESSAGE = 'Form has been submitted successfully!';
    private const DEFAULT_ERROR_MESSAGE = 'Sorry, there was an error submitting the form. Please try again.';

    #[Section(
        self::SECTION_PROCESSING,
        label: 'Processing',
        icon: __DIR__.'/Icons/'.self::SECTION_PROCESSING.'.svg',
        order: 1,
    )]
    #[DefaultValue('settings.processing.ajax')]
    #[Input\Boolean(
        label: 'Use AJAX',
        instructions: 'Use built-in AJAX for this form when handling validation and submission of the form',
        order: 1,
    )]
    public bool $ajax = false;

    #[Section(self::SECTION_PROCESSING)]
    #[DefaultValue('settings.processing.showIndicator')]
    #[Input\Boolean(
        label: 'Show Processing Indicator on Submit',
        instructions: 'Show a spinner icon on the submit button when the user submits the form until it finishes processing.',
        order: 2,
    )]
    public bool $showProcessingSpinner = false;

    #[Section(self::SECTION_PROCESSING)]
    #[DefaultValue('settings.processing.showText')]
    #[Input\Boolean(
        label: 'Show Processing Text on Submit',
        instructions: "Show 'processing' text on the submit button when the user submits the form until it finishes processing.",
        order: 3,
    )]
    public bool $showProcessingText = false;

    #[Translatable]
    #[Section(self::SECTION_PROCESSING)]
    #[VisibilityFilter('Boolean(showProcessingText)')]
    #[DefaultValue('settings.processing.processingText')]
    #[Input\Text(
        'Processing Text',
        instructions: "Enter the text you'd like to appear on the submit button when the form is processing",
        order: 4,
    )]
    public string $processingText = 'Processing...';

    #[Section(
        self::SECTION_SUCCESS_AND_ERRORS,
        label: 'Success & Errors',
        icon: __DIR__.'/Icons/'.self::SECTION_SUCCESS_AND_ERRORS.'.svg',
        order: 2,
    )]
    #[Validators\Required]
    #[DefaultValue('settings.successAndErrors.successBehavior')]
    #[Input\Select(
        instructions: "Select how you'd like the success return of this form to be handled. May also be overridden at the template level.",
        order: 1,
        options: SuccessBehaviorOptionsGenerator::class,
    )]
    public string $successBehavior = self::SUCCESS_BEHAVIOR_RELOAD;

    #[Section(self::SECTION_SUCCESS_AND_ERRORS)]
    #[ValueGenerator(SuccessTemplateGenerator::class)]
    #[VisibilityFilter('successBehavior === "'.self::SUCCESS_BEHAVIOR_LOAD_SUCCESS_TEMPLATE.'"')]
    #[DefaultValue('settings.successAndErrors.successTemplate')]
    #[Input\Select(
        instructions: "Select the template you'd like to replace the form in the page after a successful submit.",
        order: 2,
        emptyOption: 'Choose a template',
        options: SuccessTemplateOptions::class,
    )]
    public ?string $successTemplate;

    #[Section(self::SECTION_SUCCESS_AND_ERRORS)]
    #[VisibilityFilter('successBehavior === "'.self::SUCCESS_BEHAVIOR_REDIRECT_RETURN_URL.'"')]
    #[DefaultValue('settings.successAndErrors.returnUrl')]
    #[Input\Text(
        label: 'Return URL',
        instructions: 'Set a URL for the form to be redirected to after successful submit.',
        order: 3,
    )]
    public string $returnUrl = '';

    #[Translatable]
    #[Section(self::SECTION_SUCCESS_AND_ERRORS)]
    #[DefaultValue('settings.successAndErrors.successMessage')]
    #[Input\TextArea(
        instructions: 'Enter text to be shown at the top of the form if the submit is successful (AJAX), or load in your template with {{ form.settings.successMessage }}.',
        order: 4,
        placeholder: 'e.g. Form has been submitted successfully!',
    )]
    public string $successMessage = self::DEFAULT_SUCCESS_MESSAGE;

    #[Translatable]
    #[Section(self::SECTION_SUCCESS_AND_ERRORS)]
    #[DefaultValue('settings.successAndErrors.errorMessage')]
    #[Input\TextArea(
        instructions: 'Enter the text to be shown at the top of the form if there are any errors in the form after submit (AJAX), or load in your template with {{ form.settings.errorMessage }}.',
        order: 5,
        placeholder: 'e.g. Sorry, there was an error submitting the form. Please try again.',
    )]
    public string $errorMessage = self::DEFAULT_ERROR_MESSAGE;

    #[Section(
        self::SECTION_LIMITS,
        label: 'Limits',
        icon: __DIR__.'/Icons/'.self::SECTION_LIMITS.'.svg',
        order: 3,
    )]
    #[Edition(Edition::PRO)]
    #[DefaultValue('settings.limits.duplicateCheck')]
    #[Input\Select(
        label: 'Duplicate Check',
        instructions: 'Select an option for restricting users when submitting this form.',
        order: 1,
        options: FormLimitingOptions::class,
    )]
    public string $duplicateCheck = FormLimiting::NO_LIMIT;

    #[Section(self::SECTION_LIMITS)]
    #[Edition(Edition::PRO)]
    #[Edition(Edition::LITE)]
    #[ValueTransformer(DateTimeTransformer::class)]
    #[Input\DatePicker(
        label: 'Stop Submissions After Date',
        instructions: 'Set a date after which this form will no longer accept new submissions.',
        order: 2,
    )]
    public ?\DateTime $stopSubmissionsAfter = null;

    public function getSuccessMessage(): string
    {
        return $this->successMessage ?: self::DEFAULT_SUCCESS_MESSAGE;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage ?: self::DEFAULT_ERROR_MESSAGE;
    }
}
