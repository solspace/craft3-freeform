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

namespace Solspace\Freeform\Library\Configuration;

use Solspace\Freeform\Freeform;

class CraftPluginConfiguration implements ConfigurationInterface
{
    public const CONTEXT = 'freeform';

    /**
     * @param string $key
     *
     * @return null|mixed
     */
    public function get($key)
    {
        $settings = Freeform::getInstance()->getSettings();

        return $settings->{$key} ?? null;
    }
}
