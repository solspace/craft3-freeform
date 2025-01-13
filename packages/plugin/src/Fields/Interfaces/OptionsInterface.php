<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields\Interfaces;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;

interface OptionsInterface
{
    public function getOptions(): OptionCollection;

    public function getLabels(): array;

    public function getLabelsAsString(): string;
}
