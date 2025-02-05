<?php

namespace Solspace\Freeform\Integrations\Single\PostForwarding\EventListeners;

use craft\elements\Asset;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Events\PostForwarding\PostForwardingEvent;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Single\PostForwarding\PostForwarding;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\IsolatedTwig;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use yii\base\Event;

class PostForwardingTrigger extends FeatureBundle
{
    private const VALID_OPTIONS = [
        RequestOptions::MULTIPART,
        RequestOptions::FORM_PARAMS,
        RequestOptions::JSON,
        RequestOptions::BODY,
    ];

    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private IsolatedTwig $isolatedTwig,
        private IntegrationLoggerProvider $loggerProvider,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_AFTER_SUBMIT,
            [$this, 'sendPostPayload']
        );

        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this, 'attachToJson']
        );
    }

    public function sendPostPayload(SubmitEvent $event): void
    {
        $form = $event->getForm();
        $submission = $form->getSubmission();

        if ($form->isDisabled()->payload || $form->isMarkedAsSpam()) {
            return;
        }

        $integration = $this->integrationsProvider->getSingleton($form, PostForwarding::class);
        if (!$integration) {
            return;
        }

        $logger = $this->loggerProvider->getLogger($integration);

        $url = $integration->getUrl();
        $url = $this->isolatedTwig->render($url, ['form' => $form, 'submission' => $submission]);
        $triggerPhrase = $integration->getErrorTrigger();
        $triggerPhrase = $this->isolatedTwig->render($triggerPhrase, ['form' => $form, 'submission' => $submission]);

        if (!$url) {
            $logger->debug('POST forwarding URL is not set', ['form' => $form->getHandle(), 'submission' => $submission?->id]);

            return;
        }

        $fields = $form->getLayout()->getFields();

        $payload = [];
        $files = [];

        foreach ($fields as $field) {
            if (!$field->getHandle()) {
                continue;
            }

            if ($field instanceof FileUploadField && $integration->isSendFiles()) {
                $assets = $field->getAssets()->all();

                /** @var Asset $asset */
                foreach ($assets as $asset) {
                    $resource = $asset->getVolume()->getFileStream($asset->getPath());
                    $files[] = [
                        'name' => $field->getHandle(),
                        'contents' => $resource,
                        'filename' => $asset->getFilename(),
                    ];
                }
            } else {
                $payload[$field->getHandle()] = $field->getValue();
            }
        }

        $csrfTokenName = \Craft::$app->config->general->csrfTokenName;

        $payload[$csrfTokenName] = \Craft::$app->request->csrfToken;
        $payload['submission-id'] = $submission->id;
        $payload['submission-token'] = $submission->token;
        $payload['submission-title'] = $submission->title;
        $payload['submission-ip'] = $submission->ip;

        $payloadEvent = new PostForwardingEvent(
            new Client(),
            new Request('POST', $url),
            $url,
            [],
            $payload
        );

        Event::trigger(PostForwarding::class, PostForwarding::EVENT_POST_FORWARDING, $payloadEvent);
        if (!$payloadEvent->isValid) {
            $logger->debug('POST forwarding event was not valid', ['form' => $form->getHandle(), 'submission' => $submission?->id]);

            return;
        }

        $client = $payloadEvent->getClient();
        $request = $payloadEvent->getRequest();

        $options = $payloadEvent->getOptions();
        $payload = $payloadEvent->getPayload();

        $isOptionValid = array_intersect(array_keys($options), self::VALID_OPTIONS);
        if (!$isOptionValid) {
            if (empty($files) || !$integration->isSendFiles()) {
                $options[RequestOptions::FORM_PARAMS] = $payload;
            } else {
                $options[RequestOptions::MULTIPART] = array_merge(
                    $files,
                    array_map(
                        fn ($key, $value) => ['name' => $key, 'contents' => $value],
                        array_keys($payload),
                        array_values($payload),
                    ),
                );
            }
        }

        $baseLogger = Freeform::getInstance()->logger->getLogger(FreeformLogger::PAYLOAD_FORWARDING);

        $logger->debug(
            'Sending POST payload',
            [
                'form' => $form->getHandle(),
                'submission' => $submission?->id,
                'url' => $url,
                'payload' => $payload,
            ],
        );

        try {
            $response = $client->send($request, $options);
            $status = $response->getStatusCode();

            $logContext = [
                'url' => $url,
                'form' => $form->getHandle(),
                'submission' => $submission?->id,
                'response' => (string) $response->getBody(),
            ];

            if ($status >= 200 && $status < 300) {
                if ($triggerPhrase) {
                    if (false !== strripos($logContext['response'], $triggerPhrase)) {
                        $baseLogger->error('POST forwarding failed', [$logContext]);
                    }
                }

                $logger->info('POST forwarding successful', $logContext);
            } else {
                $baseLogger->error('POST forwarding failed', [$logContext]);
            }
        } catch (\Exception $e) {
            $baseLogger->error(
                'POST forwarding could not send payload',
                [
                    'url' => $url,
                    'form' => $form->getHandle(),
                    'submission' => $submission?->id,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    public function attachToJson(OutputAsJsonEvent $event): void
    {
        $form = $event->getForm();

        if ($form->isDisabled()->payload) {
            return;
        }

        $integration = $this->integrationsProvider->getSingleton($form, PostForwarding::class);
        if (!$integration) {
            return;
        }

        $event->add('postForwarding', [
            'url' => $integration->getUrl(),
            'errorTrigger' => $integration->getErrorTrigger(),
            'sendFiles' => $integration->isSendFiles(),
        ]);
    }
}
