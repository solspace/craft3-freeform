<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\Interfaces\EncryptionInterface;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\NoEmailPresenceInterface;
use Solspace\Freeform\Fields\Traits\EncryptionTrait;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Attributes\Attributes;

#[Type(
    name: 'Signature',
    typeShorthand: 'signature',
    iconPath: __DIR__.'/../Icons/signature.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/signature.ejs',
)]
class SignatureField extends AbstractField implements ExtraFieldInterface, EncryptionInterface, NoEmailPresenceInterface
{
    use EncryptionTrait;

    #[Input\Integer(
        label: 'Width of Pad',
        instructions: 'Specify a value in pixels.',
    )]
    protected int $width = 400;

    #[Input\Integer(
        label: 'Height of Pad',
        instructions: 'Specify a value in pixels.',
    )]
    protected int $height = 100;

    #[Input\Boolean(
        label: "Show 'Clear' button",
        instructions: 'Allows user to erase and start over.',
    )]
    protected bool $showClearButton = true;

    #[Input\ColorPicker(
        label: 'Border color of Pad',
    )]
    protected string $borderColor = '#999999';

    #[Input\ColorPicker(
        label: 'Background color of Pad',
    )]
    protected string $backgroundColor = 'rgba(0,0,0,0)';

    #[Input\ColorPicker(
        label: 'Pen color',
    )]
    protected string $penColor = '#000000';

    #[Input\Integer(
        label: 'Pen dot size',
        instructions: 'The size of the dot when drawing on the pad.',
        step: 0.1,
    )]
    protected float $penDotSize = 2.5;

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_SIGNATURE;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function isShowClearButton(): bool
    {
        return $this->showClearButton;
    }

    public function getBorderColor(): string
    {
        return $this->borderColor;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getPenColor(): string
    {
        return $this->penColor;
    }

    public function getPenDotSize(): float
    {
        return $this->penDotSize;
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Expects the contents of the file in Base64 format.';
        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    /**
     * Assemble the Input HTML string.
     */
    protected function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->set('type', 'button')
            ->set('data-signature-clear')
        ;

        $hasMarginStyle = false;
        foreach ($attributes as $attribute) {
            [$key, $value] = $attribute;
            if ('style' === strtolower($key)) {
                if (str_contains($value, 'margin')) {
                    $hasMarginStyle = true;
                }
            }
        }

        if (!$hasMarginStyle) {
            $attributes->replace('style', 'margin-top: 10px;');
        }

        $inputAttributes = (new Attributes())
            ->clone()
            ->set('type', 'hidden')
            ->set('name', $this->getHandle())
            ->set('value', $this->getValue())
            ->set($this->getRequiredAttribute())
        ;

        $output = '<div class="freeform-signature-wrapper" style="position: relative;">';
        $output .= '<input'.$inputAttributes.' />';

        $canvasAttributes = (new Attributes())
            ->set('style', 'padding: 1px; display: block; border-radius: 5px;')
            ->set('width', $this->getWidth())
            ->set('height', $this->getHeight())
            ->set('id', $this->getIdAttribute())
            ->set('data-pen-color', $this->getPenColor())
            ->set('data-dot-size', $this->getPenDotSize())
            ->set('data-border-color', $this->getBorderColor())
            ->set('data-background-color', $this->getBackgroundColor())
            ->set('data-signature-field')
        ;

        $output .= '<canvas'.$canvasAttributes.'>Your browser does not support the Signature field</canvas>';

        if ($this->showClearButton) {
            $output .= '<button'.$attributes.'>';
            $output .= Freeform::t('Clear');
            $output .= '</button>';
        }

        $output .= '</div>';

        return $output;
    }
}
