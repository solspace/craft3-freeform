<?php

namespace Solspace\Freeform\Integrations\Elements;

use Solspace\Freeform\Bundles\Integrations\Elements\ElementFieldMappingHelper;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationTypeProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Events\Integrations\ElementIntegrations\ConnectEvent;
use Solspace\Freeform\Events\Integrations\ElementIntegrations\ValidateEvent;
use Solspace\Freeform\Events\Integrations\RegisterIntegrationTypesEvent;
use Solspace\Freeform\Events\Mailer\RenderEmailEvent;
use Solspace\Freeform\Events\Submissions\ProcessSubmissionEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\ClassMapHelper;
use Solspace\Freeform\Library\Integrations\Types\Elements\ElementIntegrationInterface;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use Solspace\Freeform\Services\MailerService;
use yii\base\Event;

class ElementsBundle extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private ElementFieldMappingHelper $mappingHelper,
        private IntegrationTypeProvider $typeProvider,
        private IntegrationLoggerProvider $loggerProvider,
    ) {
        Event::on(
            IntegrationsService::class,
            IntegrationsService::EVENT_REGISTER_INTEGRATION_TYPES,
            [$this, 'registerTypes']
        );

        Event::on(
            Form::class,
            Form::EVENT_BEFORE_VALIDATE,
            [$this, 'validate']
        );

        Event::on(
            Submission::class,
            Submission::EVENT_PROCESS_SUBMISSION,
            [$this, 'connect']
        );
    }

    public static function isProOnly(): bool
    {
        return true;
    }

    public function registerTypes(RegisterIntegrationTypesEvent $event): void
    {
        $path = \Craft::getAlias('@freeform/Integrations/Elements');

        $classMap = ClassMapHelper::getMap($path);
        $classes = array_keys($classMap);

        foreach ($classes as $class) {
            $event->addType($class);
        }
    }

    public function validate(ValidationEvent $event): void
    {
        $form = $event->getForm();

        $integrations = $this->getElementIntegrations($form);
        foreach ($integrations as $integration) {
            $element = $integration->buildElement($form);
            $logger = $this->loggerProvider->getLogger($integration);

            $event = new ValidateEvent($form, $integration, $element);
            Event::trigger(
                ElementIntegrationInterface::class,
                ElementIntegrationInterface::EVENT_BEFORE_VALIDATE,
                $event,
            );

            if (!$event->isValid) {
                $logger->debug('Element integration validation skipped', ['form' => $form->getHandle()]);

                continue;
            }

            $element->validate();

            Event::trigger(
                ElementIntegrationInterface::class,
                ElementIntegrationInterface::EVENT_AFTER_VALIDATE,
                $event,
            );

            if ($element->hasErrors()) {
                $this->mappingHelper->attachErrors($form, $element, $integration);
                $logger->debug('Element integration validation failed', ['form' => $form->getHandle(), 'errors' => $element->getErrors()]);
            }
        }
    }

    public function connect(ProcessSubmissionEvent $event): void
    {
        $form = $event->getForm();

        if (!$event->isValid) {
            return;
        }

        if ($form->isDisabled()->elements) {
            return;
        }

        $integrations = $this->getElementIntegrations($form);
        foreach ($integrations as $integration) {
            $element = $integration->buildElement($form);
            $logger = $this->loggerProvider->getLogger($integration);
            $integration->onBeforeConnect($form, $element);

            $event = new ConnectEvent($form, $integration, $element);
            Event::trigger(
                $integration,
                ElementIntegrationInterface::EVENT_BEFORE_CONNECT,
                $event
            );

            if (!$event->isValid) {
                continue;
            }

            $isSaved = \Craft::$app->elements->saveElement($element, true, true, true);
            if (!$isSaved) {
                $errors = $element->getErrors();
                $logger->debug('Element integration failed to save', ['form' => $form->getHandle(), 'errors' => $errors]);
                foreach ($errors as $fieldErrors) {
                    $form->addErrors($fieldErrors);
                }

                continue;
            }

            $integration->onAfterConnect($form, $element);
            $this->mappingHelper->applyRelationships($form, $element, $integration);

            Event::trigger(
                $integration,
                ElementIntegrationInterface::EVENT_AFTER_CONNECT,
                $event
            );

            $logger->debug('Element integration saved', ['form' => $form->getHandle(), 'elementId' => $element->id]);

            Event::on(
                MailerService::class,
                MailerService::EVENT_BEFORE_RENDER,
                function (RenderEmailEvent $event) use ($element) {
                    $value = $event->getFieldValue('element');
                    if (null === $value) {
                        $value = $element;
                    } elseif (\is_array($value)) {
                        $value[] = $element;
                    } else {
                        $value = [$value, $element];
                    }

                    $event->setFieldValue('element', $value);
                }
            );
        }
    }

    /**
     * @return ElementIntegrationInterface[]
     */
    private function getElementIntegrations(Form $form): array
    {
        $integrations = $this->integrationsProvider->getForForm($form, ElementIntegrationInterface::class);

        return array_values(
            array_filter(
                $integrations,
                fn (ElementIntegrationInterface $integration) => $integration->isConnectable()
            )
        );
    }
}
