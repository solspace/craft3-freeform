<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers\Mutations;

use craft\errors\GqlException;
use craft\gql\base\ElementMutationResolver;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Bundles\GraphQL\GqlPermissions;
use Solspace\Freeform\Events\Forms\GraphQLRequestEvent;
use Solspace\Freeform\Events\Forms\PrepareAjaxResponsePayloadEvent;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use yii\base\Event;

class SubmissionMutationResolver extends ElementMutationResolver
{
    protected array $immutableAttributes = ['id', 'uid'];

    /**
     * @throws Error
     * @throws FreeformException
     * @throws GqlException
     */
    public function saveSubmission(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): ?array
    {
        if (!GqlPermissions::canCreateAllSubmissions() && !GqlPermissions::canCreateSubmissions($context->uid)) {
            throw new Error('Unable to create Freeform submissions.');
        }

        $form = $this->getResolutionData('form');
        if (!$form) {
            throw new Error('Form with ID {id} not found', [
                'id' => $context->id,
            ]);
        }

        $properties = [];
        if (!empty($arguments['formProperties'])) {
            $properties = $arguments['formProperties'];
        }

        $form->registerContext($properties);

        $form->setGraphQLPosted(true);
        $form->setGraphQLArguments($arguments);

        $freeform = Freeform::getInstance();

        $formsService = $freeform->forms;
        $submissionsService = $freeform->submissions;

        $request = \Craft::$app->getRequest();

        $graphqlEvent = new GraphQLRequestEvent($form, $request, $arguments);
        Event::trigger(Form::class, Form::EVENT_GRAPHQL_REQUEST, $graphqlEvent);

        $requestHandled = $form->handleRequest($request);

        $submission = $form->getSubmission();

        if ($requestHandled && $form->isValid() && !$form->getActions()) {
            $submissionsService->handleSubmission($form);

            $form->reset();
            $form->persistState();
        }

        $returnUrl = $formsService->getReturnUrl($form);

        $userErrors = [];

        foreach ($form->getLayout()->getFields() as $field) {
            if ($field->hasErrors()) {
                $errors = [];
                $errors[$field->getContentGqlHandle()] = $field->getErrors();

                $userErrors[] = $errors;
            }
        }

        if (\count($form->getErrors()) > 0) {
            $userErrors[] = $form->getErrors();
        }

        if (\count($form->getActions()) > 0) {
            $userErrors[] = $form->getActions();
        }

        if (!empty($userErrors)) {
            throw new Error(json_encode($userErrors));
        }

        $form->setFinished(true);

        $success = !$form->hasErrors() && empty($fieldErrors) && !$form->getActions();

        $spamReasons = $submission->getSpamReasons();
        if (\count($spamReasons) > 0) {
            $spamReasons = json_encode($spamReasons);
        } else {
            $spamReasons = null;
        }

        $settings = $form->getSettings();

        $payload = [
            'success' => $success,
            'hash' => $form->getHash(),
            'multiPage' => $form->isMultiPage(),
            'finished' => $form->isFinished(),
            'submissionId' => $submission->getId(),
            'submissionToken' => $submission->token,
            'duplicate' => $form->isDuplicate(),
            'onSuccess' => $settings->getBehavior()->successBehavior,
            'returnUrl' => $returnUrl,
            'html' => $form->render(),
            'id' => $submission->getId(),
            'dateCreated' => $submission->getSubmissionDate(),
            'dateUpdated' => $submission->dateUpdated,
            'isSpam' => $submission->isSpam,
            'spamReasons' => $spamReasons,
            'user' => $submission->getUser(),
        ];

        if (!empty($arguments['captcha'])) {
            $payload['captcha'] = $arguments['captcha'];
        }

        if (!empty($arguments['honeypot'])) {
            $payload['honeypot'] = $arguments['honeypot'];
        }

        if (!empty($arguments['gtm'])) {
            $payload['gtm'] = $arguments['gtm'];
        }

        if (!empty($arguments['postForwarding'])) {
            $payload['postForwarding'] = $arguments['postForwarding'];
        }

        if (!empty($arguments['javascriptTest'])) {
            $payload['javascriptTest'] = $arguments['javascriptTest'];
        }

        $generalConfig = \Craft::$app->getConfig()->getGeneral();
        $isCsrfEnabled = $generalConfig->enableCsrfProtection;
        $csrfTokenName = $generalConfig->csrfTokenName;
        if ($isCsrfEnabled && $csrfTokenName && !empty($arguments[$csrfTokenName])) {
            $payload[$csrfTokenName] = $arguments[$csrfTokenName];
        }

        // Allows field definitions specified in the response to be resolved
        foreach ($arguments as $key => $value) {
            $payload[$key] = $value;
        }

        $payload['assets'] = null;
        $assetsFields = $form->getLayout()->getFields(FileUploadField::class);
        foreach ($assetsFields as $assetsField) {
            $assets = $submission->getAssets($assetsField->getContentGqlHandle());
            foreach ($assets as $asset) {
                $payload['assets'][] = $asset;
            }
        }

        $event = new PrepareAjaxResponsePayloadEvent($form, $payload);
        Event::trigger(Form::class, Form::EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD, $event);

        $eventPayload = $event->getPayload();

        if (!empty($eventPayload['freeform_payload'])) {
            $payload['freeformPayload'] = $eventPayload['freeform_payload'];
        }

        return $payload;
    }
}
