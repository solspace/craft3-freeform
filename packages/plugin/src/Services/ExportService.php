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

namespace Solspace\Freeform\Services;

use craft\helpers\UrlHelper;
use Solspace\Freeform\Freeform;

class ExportService extends BaseService
{
    public function getNavigation(): array
    {
        return [
            'profiles' => [
                'title' => Freeform::t('Profiles'),
                'url' => UrlHelper::cpUrl('freeform/export/profiles'),
            ],
            'notifications' => [
                'title' => Freeform::t('Notifications'),
                'url' => UrlHelper::cpUrl('freeform/export/notifications'),
            ],
        ];
    }
}
