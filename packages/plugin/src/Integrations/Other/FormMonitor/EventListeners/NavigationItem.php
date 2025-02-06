<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use Solspace\Freeform\Events\Freeform\RegisterCpSubnavItemsEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Other\FormMonitor\Controllers\FormMonitorController;
use Solspace\Freeform\Integrations\Other\FormMonitor\Providers\FormMonitorProvider;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class NavigationItem extends FeatureBundle
{
    public function __construct(
        private FormMonitorProvider $formMonitorProvider
    ) {
        Event::on(
            Freeform::class,
            Freeform::EVENT_REGISTER_SUBNAV_ITEMS,
            [$this, 'addFormMonitorNavigation']
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            [$this, 'registerRoutes'],
        );

        $this->registerController('form-monitor', FormMonitorController::class);
    }

    public function addFormMonitorNavigation(RegisterCpSubnavItemsEvent $event): void
    {
        if (!$this->formMonitorProvider->isFormMonitorEnabled()) {
            return;
        }

        $event->addSubnavItem(
            'form-monitor',
            Freeform::t('Form Monitor'),
            'freeform/form-monitor',
            'settings'
        );
    }

    public function registerRoutes(RegisterUrlRulesEvent $event): void
    {
        $event->rules['freeform/form-monitor'] = 'freeform/forms';
        $event->rules['freeform/form-monitor/<id:\d+>/tests'] = 'freeform/forms';
        $event->rules['freeform/api/form-monitor/forms'] = 'freeform/form-monitor/available-forms';
        $event->rules['freeform/api/form-monitor/stats'] = 'freeform/form-monitor/stats';
        $event->rules['freeform/api/form-monitor/forms/<id:\d+>/tests'] = 'freeform/form-monitor/tests';
    }
}
