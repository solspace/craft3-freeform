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

namespace Solspace\Freeform\Fields\Implementations\ValueGenerators;

use Solspace\Freeform\Attributes\Property\ValueGeneratorInterface;

class AssetSourceGenerator implements ValueGeneratorInterface
{
    public function generateValue(?object $referenceObject): ?int
    {
        $volumes = \Craft::$app->getVolumes()->getViewableVolumes();

        if (\count($volumes)) {
            return $volumes[0]->id;
        }

        return null;
    }
}
