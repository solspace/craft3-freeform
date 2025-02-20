<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor;

use craft\helpers\DateTimeHelper;
use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators\Required;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\Transformers\ManifestTransformer;
use Solspace\Freeform\Library\Integrations\APIIntegration;

#[Edition(Edition::PRO)]
#[Type(
    name: 'Form Monitor',
    type: Type::TYPE_OTHER,
    version: 'v1',
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class FormMonitor extends APIIntegration
{
    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Hidden]
    private string $apiKey = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Hidden]
    private string $requestToken = '';

    #[Required]
    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[Input\Text(
        label: 'URL the Form Monitor should access to check the form',
        instructions: 'This is the URL that Form Monitor will use to check the form. It should be a publicly accessible URL and contain the form.',
        placeholder: 'https://example.com/contact-us',
    )]
    private string $testUrl = '';

    #[Required]
    #[Input\Text(
        label: 'Error Notification Email',
        instructions: 'Email address to receive notifications about the form.',
        placeholder: 'notices@example.com',
    )]
    private string $email = '';

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getRequestToken(): string
    {
        return $this->requestToken;
    }

    public function setRequestToken(string $requestToken): void
    {
        $this->requestToken = $requestToken;
    }

    public function getTestUrl(): string
    {
        return $this->getProcessedValue($this->testUrl);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getApiRootUrl(): string
    {
        return 'https://api.formmonitor.com/v1';
    }

    public function checkConnection(Client $client): bool
    {
        try {
            $response = $client->get($this->getEndpoint('/me'));

            return 200 === $response->getStatusCode();
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function acknowledgeSubmission(Client $client, Form $form, Submission $submission, string $requestId): void
    {
        $isSuccessful = !$form->hasErrors() && $submission->getId();
        $errors = [];

        if (!$isSuccessful) {
            $fieldErrors = [];
            foreach ($form->getLayout()->getFields() as $field) {
                if ($field->hasErrors()) {
                    $fieldErrors[$field->getHandle()] = $field->getErrors();
                }
            }

            $errors = [
                'fields' => $fieldErrors,
                'form' => $form->getErrors(),
            ];
        }

        $endpoint = $this->getEndpoint('/submissions/acknowledgement');
        $client->post(
            $endpoint,
            [
                'json' => [
                    'requestId' => $requestId,
                    'submissionId' => $submission->getId(),
                    'status' => $isSuccessful ? 'success' : 'fail',
                    'errors' => $errors,
                ],
            ]
        );
    }

    public function fetchTests(Client $client, Form $form, array $options = []): array
    {
        $endpoint = $this->getEndpoint('/forms/'.$form->getId().'/tests');
        $response = $client->get($endpoint, ['query' => $options]);
        $data = json_decode((string) $response->getBody(), true);

        // Format dates according to Craft's settings
        if (isset($data['tests']) && \is_array($data['tests'])) {
            foreach ($data['tests'] as &$test) {
                $dateFields = ['dateAttempted', 'dateCompleted'];
                foreach ($dateFields as $field) {
                    if (isset($test[$field])) {
                        $date = DateTimeHelper::toDateTime($test[$field]);
                        if ($date) {
                            $test[$field] = \Craft::$app->getFormatter()->asDatetime(
                                $date,
                                \Craft::$app->getLocale()->getDateTimeFormat('short')
                            );
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function fetchStats(Client $client, Form $form): array
    {
        $endpoint = $this->getEndpoint('/forms/'.$form->getId().'/stats');
        $response = $client->get($endpoint);

        return json_decode((string) $response->getBody(), true);
    }

    public function sendManifest(Client $client, Form $form, ManifestTransformer $transformer): void
    {
        $endpoint = $this->getEndpoint('forms/'.$form->getId());
        $payload = [
            'url' => $this->getTestUrl(),
            'email' => $this->getEmail(),
            'manifest' => $transformer->transform($form),
        ];

        $client->put($endpoint, ['json' => $payload]);
    }

    public function deleteManifest(Client $client, Form $form): void
    {
        $endpoint = $this->getEndpoint('forms/'.$form->getId());

        $client->delete($endpoint);
    }

    protected function getProcessableFields(string $category): array
    {
        return [];
    }
}
