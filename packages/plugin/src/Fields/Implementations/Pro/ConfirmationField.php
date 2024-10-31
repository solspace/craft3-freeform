<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Implementations\Field\FieldTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Interfaces\EncryptionInterface;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\MaxLengthInterface;
use Solspace\Freeform\Fields\Interfaces\NoEmailPresenceInterface;
use Solspace\Freeform\Fields\Interfaces\PlaceholderInterface;
use Solspace\Freeform\Fields\Interfaces\RecipientInterface;
use Solspace\Freeform\Fields\Interfaces\TextInterface;
use Solspace\Freeform\Fields\Traits\EncryptionTrait;
use Solspace\Freeform\Fields\Traits\MaxLengthTrait;
use Solspace\Freeform\Fields\Traits\PlaceholderTrait;
use Solspace\Freeform\Library\Exceptions\FreeformException;

#[Type(
    name: 'Confirm',
    typeShorthand: 'confirm',
    iconPath: __DIR__.'/../Icons/confirm.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/confirmation.ejs',
)]
class ConfirmationField extends AbstractField implements ExtraFieldInterface, PlaceholderInterface, EncryptionInterface, NoEmailPresenceInterface, MaxLengthInterface
{
    use EncryptionTrait;
    use MaxLengthTrait;
    use PlaceholderTrait;

    #[ValueTransformer(FieldTransformer::class)]
    #[Input\Field(
        label: 'Target field',
        instructions: 'The field that should be matched and validated against.',
        emptyOption: 'Select a field',
        implements: [
            TextInterface::class,
            RecipientInterface::class,
        ],
    )]
    protected ?FieldInterface $targetField = null;

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_CONFIRMATION;
    }

    public function getTargetField(): ?FieldInterface
    {
        return $this->targetField;
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $field = $this->getForm()->getLayout()->getField($this->getTargetField()->getUid());

        $description = $this->getContentGqlDescription();
        $description[] = 'Value must match the "'.$field->getLabel().'" field value.';

        if (!empty($this->getMaxLength())) {
            $description[] = 'Max length: '.$this->getMaxLength().'.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    protected function getInputHtml(): string
    {
        try {
            $field = $this->getTargetField();
            if (!$field) {
                return 'No target field chosen.';
            }

            $attributes = $field
                ->getAttributes()
                ->getInput()
                ->clone()
                ->merge($this->getAttributes()->getInput()->toArray())
                ->replace(
                    'placeholder',
                    $this->getPlaceholder() ? $this->translate('placeholder', $this->getPlaceholder()) : false
                )
                ->replace('type', $field->getType())
                ->replace('name', $this->getHandle())
                ->replace('id', $this->getIdAttribute())
                ->replace('value', $this->getValue())
            ;

            $output = $field->getInputHtml();

            return preg_replace('/<(\w+)[^\/>]*\/?>/', "<$1{$attributes}>", $output);
        } catch (FreeformException $exception) {
            return '<input'.$this->getAttributes()->getInput().' />';
        }
    }
}
