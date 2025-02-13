<?php

namespace Solspace\Freeform\Form\Settings\Implementations;

use Solspace\Freeform\Attributes\Form\SettingNamespace;
use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Implementations\Attributes\FormAttributesTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Limitation;
use Solspace\Freeform\Attributes\Property\Middleware;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\Translatable;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Attributes\Property\ValueGenerator;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Fields\Interfaces\BooleanInterface;
use Solspace\Freeform\Form\Settings\Implementations\Options\FormattingTemplateOptions;
use Solspace\Freeform\Form\Settings\Implementations\Options\FormStatusOptions;
use Solspace\Freeform\Form\Settings\Implementations\Options\FormTypeOptions;
use Solspace\Freeform\Form\Settings\Implementations\Options\SiteOptions;
use Solspace\Freeform\Form\Settings\Implementations\ValueGenerators\DefaultTemplateGenerator;
use Solspace\Freeform\Form\Settings\Implementations\ValueGenerators\RandomColorGenerator;
use Solspace\Freeform\Form\Settings\Implementations\ValueGenerators\SiteValueGenerator;
use Solspace\Freeform\Form\Settings\SettingsNamespace;
use Solspace\Freeform\Form\Types\Regular;
use Solspace\Freeform\Library\Attributes\FormAttributesCollection;

#[SettingNamespace(
    'General',
    order: 1,
)]
class GeneralSettings extends SettingsNamespace
{
    private const SECTION_GENERAL = 'general';
    private const SECTION_DATA_STORAGE = 'data-storage';

    #[Translatable]
    #[Section(
        self::SECTION_GENERAL,
        label: 'General',
        icon: __DIR__.'/Icons/'.self::SECTION_GENERAL.'.svg',
        order: 1,
    )]
    #[Input\Text(
        label: 'Form Name',
        instructions: 'Enter a name for this form.',
        order: 1,
        placeholder: 'My Form',
    )]
    #[Middleware('injectInto', [
        'target' => 'handle',
        'camelize' => true,
        'bypassConditions' => [['name' => 'isNew', 'isTrue' => false]],
    ])]
    #[Validators\Required]
    public string $name = '';

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.handle')]
    #[Input\Text(
        label: 'Form Handle',
        instructions: 'Enter a name for this form that will be referred to in your templates.',
        order: 2,
        placeholder: 'myHandle',
    )]
    #[Middleware('handle')]
    #[Validators\Required]
    #[Validators\Handle]
    #[Validators\Length(100)]
    public string $handle = '';

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.type')]
    #[Validators\Required]
    #[DefaultValue('settings.general.formType')]
    #[Input\Select(
        label: 'Form Type',
        instructions: 'Select the type of form this is.',
        order: 3,
        options: FormTypeOptions::class,
    )]
    public string $type = Regular::class;

    #[Section(self::SECTION_GENERAL)]
    #[ValueGenerator(SiteValueGenerator::class)]
    #[VisibilityFilter('Boolean(context.config.sites.enabled)')]
    #[Input\Checkboxes(
        label: 'Sites',
        instructions: 'Select the sites where this form should be available.',
        order: 4,
        selectAll: true,
        options: SiteOptions::class,
    )]
    public array $sites = [];

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.translations')]
    #[VisibilityFilter('Boolean(context.config.sites.enabled)')]
    #[Input\Boolean(
        label: 'Translatable',
        instructions: 'Enable translations per site for this form.',
        order: 5,
    )]
    public bool $translations = false;

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.submissionTitle')]
    #[Validators\Required]
    #[DefaultValue('settings.general.submissionTitle')]
    #[Input\Text(
        instructions: 'How the titles of submissions should be auto-generated for this form.',
        order: 6,
    )]
    public ?string $submissionTitle = null;

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.formattingTemplate')]
    #[ValueGenerator(DefaultTemplateGenerator::class)]
    #[DefaultValue('settings.general.formattingTemplate')]
    #[Input\Select(
        label: 'Formatting Template',
        instructions: 'Select a formatting template to be used when rendering this form.',
        order: 7,
        options: FormattingTemplateOptions::class,
    )]
    public ?string $formattingTemplate = null;

    #[Translatable]
    #[Section(self::SECTION_GENERAL)]
    #[Input\Textarea(
        label: 'Form Description',
        instructions: 'Enter a description or notes for this form.',
        order: 8,
    )]
    public string $description = '';

    #[Section(self::SECTION_GENERAL)]
    #[ValueGenerator(RandomColorGenerator::class)]
    #[Input\ColorPicker(
        label: 'Form Color',
        instructions: 'Choose a color for this form (generally used in the control panel).',
        order: 9,
    )]
    public string $color = '';

    #[Section(self::SECTION_GENERAL)]
    #[Limitation('settings.tab.general.attributes')]
    #[ValueTransformer(FormAttributesTransformer::class)]
    #[Input\Attributes(
        instructions: 'Add attributes to your form elements.',
        tabs: [
            [
                'handle' => 'form',
                'label' => 'Form',
                'previewTag' => 'form',
            ],
            [
                'handle' => 'row',
                'label' => 'Row',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'success',
                'label' => 'Success',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'errors',
                'label' => 'Errors',
                'previewTag' => 'ul',
            ],
        ]
    )]
    public FormAttributesCollection $attributes;

    #[Section(
        self::SECTION_DATA_STORAGE,
        label: 'Data Storage',
        icon: __DIR__.'/Icons/'.self::SECTION_DATA_STORAGE.'.svg',
        order: 2,
    )]
    #[DefaultValue('settings.dataStorage.storeData')]
    #[Input\Boolean(
        label: 'Store Submitted Data for this Form',
        instructions: 'All submissions users make on this form will be stored in the database.',
        order: 1,
    )]
    public bool $storeData = true;

    #[Section(self::SECTION_DATA_STORAGE)]
    #[DefaultValue('settings.dataStorage.defaultStatus')]
    #[Validators\Required]
    #[Input\Select(
        instructions: 'Select the default status for each submission of this form.',
        order: 2,
        options: FormStatusOptions::class,
    )]
    public ?int $defaultStatus = null;

    #[Section(self::SECTION_DATA_STORAGE)]
    #[DefaultValue('settings.dataStorage.collectIp')]
    #[Input\Boolean(
        label: 'Collect IP Addresses',
        instructions: 'Collect and store each users IP address when submitting the form.',
        order: 3,
    )]
    public bool $collectIpAddresses = true;

    #[Section(self::SECTION_DATA_STORAGE)]
    #[Input\Boolean(
        label: 'Allow Users to Opt-in',
        instructions: 'Allow users to choose whether they want their submission data stored in the database.',
        order: 4,
    )]
    public bool $allowUsersToOptIn = false;

    #[Section(self::SECTION_DATA_STORAGE)]
    #[VisibilityFilter('Boolean(allowUsersToOptIn)')]
    #[Input\Field(
        label: 'Opt-in Checkbox',
        instructions: 'Select the checkbox field that will act as the opt-in for the user submitting the form.',
        order: 5,
        emptyOption: 'Please select a field...',
        implements: [BooleanInterface::class],
    )]
    public ?string $optInCheckbox = null;

    public function __construct()
    {
        $this->attributes = new FormAttributesCollection();
    }
}
