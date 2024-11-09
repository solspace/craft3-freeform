<?php

namespace Solspace\Freeform\Integrations\Webhooks;

use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Integrations\FailedRequestEvent;
use Solspace\Freeform\Events\Integrations\RegisterIntegrationTypesEvent;
use Solspace\Freeform\Events\Submissions\ProcessSubmissionEvent;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\ClassMapHelper;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Integrations\Types\Webhooks\WebhookIntegrationInterface;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use yii\base\Event;

class WebhooksBundle extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $formIntegrationsProvider,
    ) {
        Event::on(
            IntegrationsService::class,
            IntegrationsService::EVENT_REGISTER_INTEGRATION_TYPES,
            [$this, 'registerTypes']
        );

        Event::on(
            Submission::class,
            Submission::EVENT_PROCESS_SUBMISSION,
            [$this, 'triggerWebhooks']
        );
    }

    public static function isProOnly(): bool
    {
        return true;
    }

    public function registerTypes(RegisterIntegrationTypesEvent $event): void
    {
        $path = \Craft::getAlias('@freeform/Integrations/Webhooks');

        $classMap = ClassMapHelper::getMap($path);
        $classes = array_keys($classMap);

        foreach ($classes as $class) {
            $event->addType($class);
        }
    }

    public function triggerWebhooks(ProcessSubmissionEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isMarkedAsSpam()) {
            return;
        }

        /** @var WebhookIntegrationInterface[] $webhooks */
        $webhooks = $this->formIntegrationsProvider->getForForm($form, Type::TYPE_WEBHOOKS);
        foreach ($webhooks as $webhook) {
            try {
                $webhook->trigger($form);
            } catch (\Exception $exception) {
                $event = new FailedRequestEvent($form, $webhook, $exception);
                Event::trigger(
                    IntegrationInterface::class,
                    IntegrationInterface::EVENT_ON_FAILED_REQUEST,
                    $event
                );
            }
        }
    }
}
