<?php

namespace Solspace\Freeform\Bundles\Templates;

use Solspace\Freeform\Events\Freeform\RegisterSettingsNavigationEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Event;

class PdfTemplatesBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SettingsService::class,
            SettingsService::EVENT_REGISTER_SETTINGS_NAVIGATION,
            [$this, 'addPdfTemplatesNavigation']
        );
    }

    public function addPdfTemplatesNavigation(RegisterSettingsNavigationEvent $event): void
    {
        if (!PermissionHelper::checkPermission(Freeform::PERMISSION_PDF_TEMPLATES_ACCESS)) {
            return;
        }

        $event->addNavigationItem(
            'pdf-templates',
            'PDF Templates',
            'template-manager'
        );
    }
}
