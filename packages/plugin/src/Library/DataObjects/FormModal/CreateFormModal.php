<?php

namespace Solspace\Freeform\Library\DataObjects\FormModal;

use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators\Required;
use Solspace\Freeform\Form\Settings\Implementations\Options\FormattingTemplateOptions;

class CreateFormModal
{
    #[Required]
    #[Input\Text('Form Name')]
    private string $name = '';

    #[DefaultValue('settings.general.formattingTemplate')]
    #[Input\Select(
        options: FormattingTemplateOptions::class,
    )]
    private ?string $formattingTemplate = null;

    #[DefaultValue('settings.dataStorage.storeData')]
    #[Input\Boolean(
        label: 'Store Submitted Data for this Form',
        instructions: 'All submissions for this form will be stored in the database.',
    )]
    private bool $storeData = true;

    public function getName(): string
    {
        return $this->name;
    }

    public function getFormattingTemplate(): ?string
    {
        return $this->formattingTemplate;
    }

    public function isStoreData(): bool
    {
        return $this->storeData;
    }
}
