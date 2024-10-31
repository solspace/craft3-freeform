<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields\Implementations;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\Interfaces\DefaultValueInterface;
use Solspace\Freeform\Fields\Interfaces\EncryptionInterface;
use Solspace\Freeform\Fields\Interfaces\MaxLengthInterface;
use Solspace\Freeform\Fields\Interfaces\PlaceholderInterface;
use Solspace\Freeform\Fields\Interfaces\TextInterface;
use Solspace\Freeform\Fields\Traits\DefaultTextValueTrait;
use Solspace\Freeform\Fields\Traits\EncryptionTrait;
use Solspace\Freeform\Fields\Traits\MaxLengthTrait;
use Solspace\Freeform\Fields\Traits\PlaceholderTrait;

#[Type(
    name: 'Textarea',
    typeShorthand: 'textarea',
    iconPath: __DIR__.'/Icons/textarea.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/textarea.ejs',
)]
class TextareaField extends AbstractField implements PlaceholderInterface, TextInterface, DefaultValueInterface, EncryptionInterface, MaxLengthInterface
{
    use DefaultTextValueTrait;
    use EncryptionTrait;
    use MaxLengthTrait;
    use PlaceholderTrait;

    #[Input\TextArea(
        label: 'Default value',
        instructions: 'Enter a default value for this field',
    )]
    protected ?string $defaultValue = '';

    #[Input\Integer(
        instructions: 'The number of rows in height for this field.',
        min: 1,
    )]
    protected int $rows = 2;

    public function getType(): string
    {
        return self::TYPE_TEXTAREA;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('id', $this->getIdAttribute())
            ->setIfEmpty('rows', $this->getRows())
            ->setIfEmpty('placeholder', $this->translate('placeholder', $this->getPlaceholder()))
            ->set($this->getRequiredAttribute())
        ;

        return '<textarea'.$attributes.'>'
            .htmlentities($this->getValue())
            .'</textarea>';
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();

        if (!empty($this->maxLength)) {
            $description[] = 'Max length: '.$this->maxLength.'.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }
}
