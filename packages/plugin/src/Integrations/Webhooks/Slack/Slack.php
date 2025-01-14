<?php

namespace Solspace\Freeform\Integrations\Webhooks\Slack;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Input\TextArea;
use Solspace\Freeform\Attributes\Property\Validators\Required;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\Types\Webhooks\WebhookIntegration;
use Solspace\Freeform\Library\Logging\FreeformLogger;

#[Type(
    name: 'Slack',
    type: Type::TYPE_WEBHOOKS,
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class Slack extends WebhookIntegration
{
    #[Required]
    #[TextArea(
        label: 'Message',
        instructions: 'The message to send to Slack. You can use Twig syntax to render dynamic content.',
        placeholder: 'A new submission has been received for {{ form.name }}',
        rows: 10,
    )]
    protected string $message = '';

    public function trigger(Form $form): void
    {
        $submission = $form->getSubmission();

        $message = $this->message;
        $message = \Craft::$app->view->renderString($message, [
            'form' => $form,
            'submission' => $submission,
        ]);

        if (!$message) {
            Freeform::getInstance()
                ->logger
                ->getLogger(FreeformLogger::WEBHOOKS_INTEGRATION)
                ->warning('Slack integration has no message set')
            ;

            return;
        }

        $client = new Client();
        $client->post($this->getUrl(), ['json' => ['text' => $message]]);

        $this->logger->info('Slack webhook triggered', ['form' => $form->getHandle(), 'submission' => $submission->id]);
        $this->logger->debug('With Payload', ['text' => $message]);
    }
}
