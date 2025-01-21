<?php

namespace Solspace\Freeform\Integrations\Webhooks\Generic;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\Types\Webhooks\WebhookIntegration;

#[Type(
    name: 'Generic Webhook',
    type: Type::TYPE_WEBHOOKS,
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class Generic extends WebhookIntegration
{
    public function trigger(Form $form): void
    {
        $submission = $form->getSubmission();
        $json = [
            'form' => [
                'id' => $form->getId(),
                'name' => $form->getName(),
                'handle' => $form->getHandle(),
                'color' => $form->getColor(),
                'description' => $form->getDescription(),
                'returnUrl' => $form->getReturnUrl(),
            ],
        ];

        if ($submission) {
            $json['id'] = $submission->id;
            $json['dateCreated'] = $submission->dateCreated;
            $json['uid'] = $submission->uid;
            $json['token'] = $submission->token;
        }

        foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
            $value = $field->getValue();
            if ($field instanceof FileUploadField) {
                $value = Freeform::getInstance()->files->getAssetMetadataFromIds($value);
            }

            $json[$field->getHandle()] = $value;
        }

        $client = new Client();
        $client->post($this->getUrl(), ['json' => $json]);

        $this->logger->info('Webhook triggered', ['form' => $form->getHandle(), 'submission' => $submission->id]);
        $this->logger->debug('With Payload', $json);
    }
}
