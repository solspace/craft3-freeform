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

namespace Solspace\Freeform\Variables;

use Solspace\Freeform\Freeform;

/**
 * Class FreeformBannersVariable.
 */
class FreeformBannersVariable
{
    public function getShowDemoTemplates(): bool
    {
        $settingsService = Freeform::getInstance()->settings;
        if (!$settingsService->isAllowAdminEdit()) {
            return false;
        }

        return !(bool) $settingsService->getSettingsModel()->hideBannerDemo;
    }
}
