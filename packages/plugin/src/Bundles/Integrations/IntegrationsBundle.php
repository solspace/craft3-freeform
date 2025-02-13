<?php

namespace Solspace\Freeform\Bundles\Integrations;

use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Events\Integrations\CrmIntegrations\ProcessValueEvent;
use Solspace\Freeform\Events\Integrations\FailedRequestEvent;
use Solspace\Freeform\Fields\Implementations\Pro\DatetimeField;
use Solspace\Freeform\Library\Bundles\BundleLoader;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use yii\base\Event;

class IntegrationsBundle extends FeatureBundle
{
    public function __construct(
        private IntegrationLoggerProvider $loggerProvider,
    ) {
        Event::on(
            APIIntegrationInterface::class,
            APIIntegrationInterface::EVENT_PROCESS_VALUE,
            [$this, 'processValue']
        );

        Event::on(
            IntegrationInterface::class,
            IntegrationInterface::EVENT_ON_FAILED_REQUEST,
            [$this, 'logException']
        );

        $path = \Craft::getAlias('@freeform/Integrations');
        BundleLoader::loadBundles($path);
    }

    public function processValue(ProcessValueEvent $event)
    {
        $integrationField = $event->getIntegrationField();
        $freeformField = $event->getFreeformField();
        $value = $event->getValue();

        if (FieldObject::TYPE_ARRAY !== $integrationField->getType() && \is_array($value)) {
            $value = implode(', ', $value);
        }

        switch ($integrationField->getType()) {
            case FieldObject::TYPE_ARRAY:
                if (!\is_array($value)) {
                    if (!empty($value)) {
                        $value = [$value];
                    } else {
                        $value = [];
                    }
                }

                return $value;

            case FieldObject::TYPE_NUMERIC:
                return (int) preg_replace('/\D/', '', $value) ?: '';

            case FieldObject::TYPE_FLOAT:
                return (float) preg_replace('/[^0-9,.]/', '', $value) ?: '';

            case FieldObject::TYPE_DATE:
                if ($freeformField instanceof DatetimeField) {
                    $carbon = $freeformField->getCarbon();
                    if ($carbon) {
                        return $carbon->toDateString();
                    }
                }

                return (string) $value;

            case FieldObject::TYPE_DATETIME:
                if ($freeformField instanceof DatetimeField) {
                    $carbon = $freeformField->getCarbon();
                    if ($carbon) {
                        return $carbon->toAtomString();
                    }
                }

                return (string) $value;

            case FieldObject::TYPE_TIMESTAMP:
            case FieldObject::TYPE_MICROTIME:
                if ($freeformField instanceof DatetimeField) {
                    $carbon = $freeformField->getCarbonUtc();
                    if ($carbon) {
                        if (DatetimeField::DATETIME_TYPE_DATE === $freeformField->getDateTimeType()) {
                            $carbon->setTime(0, 0);
                        }

                        $timestamp = $carbon->getTimestamp();
                        if (FieldObject::TYPE_MICROTIME === $integrationField->getType()) {
                            $timestamp *= 1000;
                        }

                        return $timestamp;
                    }
                }

                return (int) $value;

            case FieldObject::TYPE_BOOLEAN:
                return (bool) $value;

            case FieldObject::TYPE_STRING:
            default:
                return (string) $value;
        }
    }

    public function logException(FailedRequestEvent $event): void
    {
        if ($event->isRetry() || !$event->isValid) {
            return;
        }

        $integration = $event->getIntegration();
        $logger = $this->loggerProvider->getLogger($integration);
        $exception = $event->getException();

        $message = $exception->getMessage();
        if ($exception instanceof RequestException) {
            if (method_exists($exception->getResponse(), 'getBody')) {
                $message = (string) $exception->getResponse()->getBody();
            }
        }

        $context = [
            'form' => $event->getForm()->getHandle(),
            'integration' => $integration->getHandle(),
        ];

        if (json_validate($message)) {
            $context['response'] = json_decode($message, true);
            $message = 'Failed request';
        }

        $logger->error($message, $context);

        $this->plugin()
            ->logger
            ->getLogger(FreeformLogger::INTEGRATION)
            ->error($integration->getTypeDefinition()->name.': '.$message, $context)
        ;
    }
}
