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
use Solspace\Freeform\Services\ExportService;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\HoneypotService;
use Solspace\Freeform\Services\NotificationsService;
use Solspace\Freeform\Services\Pro\ExportNotificationsService;
use Solspace\Freeform\Services\Pro\ExportProfilesService;
use Solspace\Freeform\Services\Pro\Payments\PaymentsService;
use Solspace\Freeform\Services\SettingsService;

/**
 * Class FreeformBannersVariable.
 */
class FreeformServicesVariable
{
    public function notifications(): NotificationsService
    {
        return Freeform::getInstance()->notifications;
    }

    public function exportNotifications(): ExportNotificationsService
    {
        return Freeform::getInstance()->exportNotifications;
    }

    public function exportProfiles(): ExportProfilesService
    {
        return Freeform::getInstance()->exportProfiles;
    }

    public function export(): ExportService
    {
        return Freeform::getInstance()->export;
    }

    public function forms(): FormsService
    {
        return Freeform::getInstance()->forms;
    }

    public function settings(): SettingsService
    {
        return Freeform::getInstance()->settings;
    }

    public function honeypot(): HoneypotService
    {
        return Freeform::getInstance()->honeypot;
    }

    public function payments(): PaymentsService
    {
        return Freeform::getInstance()->payments;
    }
}
