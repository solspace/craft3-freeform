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

namespace Solspace\Freeform\Fields\Traits;

use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\Translatable;

trait PlaceholderTrait
{
    #[Translatable]
    #[Section('general')]
    #[Input\Text(
        instructions: 'The text that will be shown if the field doesn\'t have a value',
        order: 4,
    )]
    protected string $placeholder = '';

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
