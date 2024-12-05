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

use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Implementations\Attributes\FieldAttributesTransformer;
use Solspace\Freeform\Attributes\Property\Input\Hidden;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Fields\Interfaces\NoRenderInterface;
use Solspace\Freeform\Library\Attributes\FieldAttributesCollection;

#[Type(
    name: 'Hidden',
    typeShorthand: 'hidden',
    iconPath: __DIR__.'/Icons/hidden.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/hidden.ejs',
)]
class HiddenField extends TextField implements NoRenderInterface
{
    protected string $instructions = '';
    protected string $placeholder = '';
    protected bool $required = false;

    #[Hidden]
    #[ValueTransformer(FieldAttributesTransformer::class)]
    protected FieldAttributesCollection $attributes;

    public function getType(): string
    {
        return self::TYPE_HIDDEN;
    }

    public function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', $this->getType())
            ->setIfEmpty('id', $this->getIdAttribute())
            ->setIfEmpty('value', $this->getValue())
        ;

        return '<input'.$attributes.' />';
    }
}
