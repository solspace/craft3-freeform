<?php

namespace Solspace\Freeform\Form\Layout\Page\Buttons;

use craft\helpers\Template;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Implementations\Attributes\PageButtonAttributesTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\Field\FieldTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\NotificationTemplates\NotificationTemplateTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Limitation;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\Translatable;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Events\Fields\CompileButtonAttributesEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Interfaces\RecipientInterface;
use Solspace\Freeform\Form\Layout\Page;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Twig\Markup;
use yii\base\Event;

class PageButtons
{
    public const ACTION_SUBMIT = 'submit';
    public const ACTION_BACK = 'back';
    public const ACTION_SAVE = 'save';

    public const INPUT_NAME_PREVIOUS_PAGE = 'form_previous_page_button';
    public const INPUT_NAME_SUBMIT = 'form_page_submit';

    public const EVENT_COMPILE_ATTRIBUTES = 'compile-attributes';

    #[Section(
        handle: 'general',
        label: 'General',
        icon: __DIR__.'/SectionIcons/button.svg',
    )]
    #[Limitation('layout.buttons')]
    #[Input\Special\PageButtonLayout(
        label: 'Button Layout',
        layouts: [
            'save back|submit',
            'back|submit save',

            'back|save submit',
            'submit back|save',

            'save|back|submit ',
            ' back|submit|save',

            'back|submit|save ',
            ' save|back|submit',

            'submit|back|save ',
            ' submit|back|save',

            ' back|submit|save ',
            ' save|back|submit ',
        ],
        elements: [
            ['value' => 'submit', 'label' => 'Submit'],
            ['value' => 'back', 'label' => 'Back'],
            ['value' => 'save', 'label' => 'Save'],
            ['value' => ' ', 'label' => 'Space'],
        ]
    )]
    private string $layout;

    #[Translatable]
    #[Section(
        handle: 'submit',
        label: 'Submit',
        icon: __DIR__.'/SectionIcons/submit.svg',
    )]
    #[Input\Text('Label', placeholder: 'Submit')]
    private string $submitLabel = 'Submit';

    #[Section(
        handle: 'back',
        label: 'Back',
        icon: __DIR__.'/SectionIcons/back.svg',
    )]
    #[VisibilityFilter('context.page.order !== 0')]
    #[Input\Boolean('Enable Back Button')]
    private bool $back = false;

    #[Translatable]
    #[Section('back')]
    #[VisibilityFilter('Boolean(buttons.back)')]
    #[VisibilityFilter('context.page.order !== 0')]
    #[Input\Text('Label', placeholder: 'Back')]
    private string $backLabel = 'Back';

    #[Edition(Freeform::EDITION_PRO)]
    #[Section(
        handle: 'save',
        label: 'Save',
        icon: __DIR__.'/SectionIcons/save.svg',
    )]
    #[Limitation('layout.buttons')]
    #[Input\Boolean('Enable Save Button')]
    private bool $save = false;

    #[Translatable]
    #[Section('save')]
    #[Limitation('layout.buttons')]
    #[VisibilityFilter('Boolean(buttons.save)')]
    #[Input\Text('Label', placeholder: 'Save')]
    private string $saveLabel = 'Save';

    #[Section('save')]
    #[Limitation('layout.buttons')]
    #[VisibilityFilter('Boolean(buttons.save)')]
    #[Input\Text(
        label: 'Redirect URL',
        instructions: 'Specify the redirect URL when saving a form. You can use `token` and `key` variables to pass the submission token and key to the URL.',
        placeholder: 'https://example.com',
    )]
    private string $saveRedirectUrl = '';

    #[Section('save')]
    #[Limitation('layout.buttons')]
    #[VisibilityFilter('Boolean(buttons.save)')]
    #[ValueTransformer(FieldTransformer::class)]
    #[Input\Field(
        label: 'Email Notification Recipient',
        instructions: 'Select an email notification recipient.',
        emptyOption: 'Select recipient...',
        implements: [RecipientInterface::class],
    )]
    private ?FieldInterface $emailField = null;

    #[Section('save')]
    #[Limitation('layout.buttons')]
    #[VisibilityFilter('Boolean(buttons.save)')]
    #[ValueTransformer(NotificationTemplateTransformer::class)]
    #[Input\NotificationTemplate(
        label: 'Email Notification Template',
        instructions: 'Select an email notification template.',
    )]
    private ?NotificationTemplate $notificationTemplate = null;

    #[Section(
        handle: 'attributes',
        label: 'Attributes',
        icon: __DIR__.'/SectionIcons/list.svg',
        order: 999,
    )]
    #[Limitation('layout.buttons')]
    #[ValueTransformer(PageButtonAttributesTransformer::class)]
    #[Input\Attributes(
        tabs: [
            [
                'handle' => 'container',
                'label' => 'Container',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'column',
                'label' => 'Column',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'buttonWrapper',
                'label' => 'Button Wrapper',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'submit',
                'label' => 'Submit',
                'previewTag' => 'button',
            ],
            [
                'handle' => 'back',
                'label' => 'Back',
                'previewTag' => 'button',
            ],
            [
                'handle' => 'save',
                'label' => 'Save',
                'previewTag' => 'button',
            ],
        ]
    )]
    private ButtonAttributesCollection $attributes;

    public function __construct(
        private Page $page,
        private TranslationProvider $translationProvider,
        array $config
    ) {
        $this->layout = $config['layout'] ?? 'save back|submit';
        $this->attributes = new ButtonAttributesCollection($config['attributes'] ?? []);
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @return array<array<Button>>
     */
    public function getParsedLayout(): array
    {
        $layout = $this->layout;
        $layout = preg_replace('/\s+/', ' ', $layout);
        $groups = explode(' ', $layout);

        $parsedLayout = [];
        foreach ($groups as $group) {
            $parsedLayout[] = explode('|', $group);
        }

        return $parsedLayout;
    }

    public function getAttributes(): ButtonAttributesCollection
    {
        $event = new CompileButtonAttributesEvent($this, $this->attributes->clone());
        Event::trigger($this, self::EVENT_COMPILE_ATTRIBUTES, $event);

        return $event->getAttributes();
    }

    public function getSubmitLabel(): string
    {
        return $this->translate('submitLabel', $this->submitLabel);
    }

    public function isBack(): bool
    {
        return 0 === $this->getPage()->getIndex() ? false : $this->back;
    }

    public function getBackLabel(): string
    {
        return $this->translate('backLabel', $this->backLabel);
    }

    public function isSave(): bool
    {
        return $this->save;
    }

    public function getSaveLabel(): string
    {
        return $this->translate('saveLabel', $this->saveLabel);
    }

    public function getSaveRedirectUrl(): string
    {
        return $this->saveRedirectUrl;
    }

    public function getEmailField(): ?FieldInterface
    {
        return $this->emailField;
    }

    public function getNotificationTemplate(): ?NotificationTemplate
    {
        return $this->notificationTemplate;
    }

    public function getSubmitRenderProps(array $customAttributes = []): Markup
    {
        return Template::raw(
            $this->getAttributes()
                ->getSubmit()
                ->clone()
                ->merge($customAttributes)
                ->replace('data-freeform-action', 'submit')
                ->replace('data-button-container', 'submit')
                ->replace('name', self::INPUT_NAME_SUBMIT)
                ->replace('type', 'submit')
        );
    }

    public function renderSubmit(array $customAttributes = []): Markup
    {
        $attributes = $this->getSubmitRenderProps($customAttributes);

        return Template::raw(
            '<button'.$attributes.'>'
            .htmlspecialchars(
                Freeform::t($this->getSubmitLabel())
            )
            .'</button>'
        );
    }

    public function getBackRenderProps(array $customAttributes = []): Markup
    {
        return Template::raw(
            $this->getAttributes()
                ->getBack()
                ->clone()
                ->merge($customAttributes)
                ->replace('data-freeform-action', 'back')
                ->replace('data-button-container', 'back')
                ->replace('name', self::INPUT_NAME_PREVIOUS_PAGE)
                ->replace('type', 'submit')
        );
    }

    public function renderBack(array $customAttributes = []): Markup
    {
        $attributes = $this->getBackRenderProps($customAttributes);

        return Template::raw(
            '<button'.$attributes.'>'
            .htmlspecialchars(
                Freeform::t($this->getBackLabel())
            )
            .'</button>'
        );
    }

    public function getSaveRenderProps(array $customAttributes = []): Markup
    {
        return Template::raw(
            $this->getAttributes()
                ->getSave()
                ->clone()
                ->merge($customAttributes)
                ->replace('data-freeform-action', 'save')
                ->replace('data-button-container', 'save')
                ->replace('type', 'submit')
        );
    }

    public function renderSave(array $customAttributes = []): Markup
    {
        $attributes = $this->getSaveRenderProps($customAttributes);

        return Template::raw(
            '<button'.$attributes.'>'
            .htmlspecialchars(
                Freeform::t($this->getSaveLabel())
            )
            .'</button>'
        );
    }

    protected function translate(?string $handle, mixed $defaultValue = null): mixed
    {
        return $this
            ->translationProvider
            ->getTranslation(
                $this->getPage(),
                $this->getPage()->getUid(),
                $handle,
                $defaultValue
            )
        ;
    }
}
