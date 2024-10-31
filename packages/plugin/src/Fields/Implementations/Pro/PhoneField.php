<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\PhoneMaskInterface;

#[Type(
    name: 'Phone',
    typeShorthand: 'phone',
    iconPath: __DIR__.'/../Icons/phone.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/text.ejs',
)]
class PhoneField extends TextField implements PhoneMaskInterface, ExtraFieldInterface
{
    protected string $customInputType = 'tel';

    #[Input\Text(
        label: 'Pattern validation',
        instructions: "Use '0' (a digit between 0-9) and other characters, e.g. '(000) 000-0000' or '+0 0000 000000'.",
    )]
    protected ?string $pattern = null;

    #[Input\Boolean(
        label: 'Use built-in javascript validation on pattern',
    )]
    protected bool $useJsMask = false;

    public function getType(): string
    {
        return self::TYPE_PHONE;
    }

    public function isUseJsMask(): bool
    {
        return $this->useJsMask;
    }

    public function getPattern(): ?string
    {
        return !empty($this->pattern) ? $this->pattern : null;
    }

    public function getInputHtml(): string
    {
        if (!$this->isUseJsMask()) {
            return parent::getInputHtml();
        }

        $pattern = $this->getPattern();
        $pattern = str_replace('x', '0', $pattern);

        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->append('class', 'form-phone-pattern-field')
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', $this->customInputType ?? 'text')
            ->setIfEmpty('id', $this->getIdAttribute())
            ->setIfEmpty('placeholder', $this->translate('placeholder', $this->getPlaceholder()))
            ->setIfEmpty('value', $this->getValue())
            ->setIfEmpty('data-masked-input', $pattern)
            ->setIfEmpty('data-pattern', $pattern)
        ;

        return '<input'.$attributes.' />';
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();

        if (!empty($this->getPattern())) {
            $description[] = 'Pattern: "'.$this->getPattern().'".';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }
}
