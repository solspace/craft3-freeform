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

use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Limitation;
use Solspace\Freeform\Attributes\Property\Section;

trait EncryptionTrait
{
    #[Section('advanced')]
    #[Limitation('layout.fields.encrypted')]
    #[Edition(Edition::PRO)]
    #[Input\Boolean(
        label: 'Encrypt field data',
        order: 1,
    )]
    protected bool $encrypted = false;

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }
}
