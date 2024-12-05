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

namespace Solspace\Freeform\Fields\Implementations\Options;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionsGeneratorInterface;
use Solspace\Freeform\Attributes\Property\Property;

class AssetSourceOptions implements OptionsGeneratorInterface
{
    public function fetchOptions(?Property $property): OptionCollection
    {
        $options = new OptionCollection();

        $volumes = \Craft::$app->getVolumes()->getViewableVolumes();

        foreach ($volumes as $volume) {
            $options->add($volume->id, $volume->name);
        }

        return $options;
    }
}
