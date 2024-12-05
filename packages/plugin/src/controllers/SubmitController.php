<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\controllers;

use Solspace\Freeform\Bundles\Form\Context\Session\Bag\SessionBag;
use Solspace\Freeform\Bundles\Form\Context\Session\SessionContext;
use Solspace\Freeform\Events\Controllers\ConfigureCORSEvent;
use Solspace\Freeform\Events\Forms\PrepareAjaxResponsePayloadEvent;
use Solspace\Freeform\Events\Forms\SubmitResponseEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\CryptoHelper;
use Solspace\Freeform\Records\SavedFormRecord;
use yii\base\Event;
use yii\filters\Cors;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SubmitController extends BaseController
{
    public const EVENT_CONFIGURE_CORS = 'configure-cors';

    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): ?Response
    {
        $request = \Craft::$app->getRequest();
        $isAjaxRequest = $request->getIsAjax();

        $form = $this->getFormFromRequest();

        $requestHandled = $form->handleRequest($request);
        $submissionsService = $this->getSubmissionsService();
        if ($requestHandled && $form->isFormPosted() && $form->isValid() && !$form->getActions() && $form->isFinished()) {
            $submissionsService->handleSubmission($form);

            $form->reset();
            $form->persistState();

            if ($isAjaxRequest) {
                return $this->toAjaxResponse($form);
            }

            $event = new SubmitResponseEvent($form, $this->response);
            Event::trigger(Form::class, Form::EVENT_ON_SUBMIT_RESPONSE, $event);

            return $event->getResponse();
        }

        $form->persistState();

        if ($isAjaxRequest) {
            return $this->toAjaxResponse($form);
        }

        return null;
    }

    public function actionQuickSave(): Response
    {
        $request = \Craft::$app->getRequest();

        $token = $request->post('token', 'qs-'.CryptoHelper::getUniqueToken(30));
        $secret = $request->post('storage-secret');
        if (!$secret) {
            throw new NotFoundHttpException('No secret provided');
        }

        $form = $this->getFormFromRequest();
        $form->handleRequest($request);

        if ($form->isMarkedAsSpam()) {
            throw new HttpException(417, 'Form is invalid');
        }

        $bag = new SessionBag($form->getId(), $form->getProperties()->toArray(), $form->getAttributes()->toArray());

        $serialized = json_encode($bag);
        $payload = base64_encode(\Craft::$app->security->encryptByKey($serialized, $secret));

        $record = SavedFormRecord::findOne(['token' => $token, 'formId' => $form->getId()]);
        if (!$record) {
            $record = new SavedFormRecord();
            $record->formId = $form->getId();
            $record->token = $token;
        }

        $record->sessionId = \Craft::$app->getSession()->getId();
        $record->payload = $payload;
        $record->save();

        Event::on(
            Form::class,
            Form::EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD,
            function (PrepareAjaxResponsePayloadEvent $event) use ($record) {
                $event->add('storageToken', $record->token);
            }
        );

        return $this->toAjaxResponse($form);
    }

    public function behaviors(): array
    {
        $generalConfig = \Craft::$app->getConfig()->getGeneral();
        $origins = $generalConfig->allowedGraphqlOrigins;
        if ('*' === $origins) {
            $origins = ['*'];
        }

        $corsHeaders = [
            'Access-Control-Request-Method' => ['POST', 'OPTIONS'],
            'Access-Control-Request-Headers' => [
                'Authorization',
                'Cache-Control',
                'Content-Type',
                'X-Craft-Token',
                'X-Requested-With',
                'HTTP_X_REQUESTED_WITH',
            ],
            'Access-Control-Allow-Credentials' => !\is_array($origins) || !\in_array('*', $origins, true),
            'Access-Control-Max-Age' => 86400,
            'Origin' => \is_array($origins) ? $origins : [$origins],
        ];

        $event = new ConfigureCORSEvent($corsHeaders);
        $this->trigger(self::EVENT_CONFIGURE_CORS, $event);

        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => $event->getHeaders(),
            ],
        ];
    }

    private function toAjaxResponse(Form $form): Response
    {
        $submission = $form->getSubmission();
        $returnUrl = $this->getFormsService()->getReturnUrl($form);

        $fieldErrors = [];
        foreach ($form->getLayout()->getFields() as $field) {
            if ($field->hasErrors()) {
                $fieldErrors[$field->getHandle()] = $field->getErrors();
            }
        }

        $success = !$form->hasErrors() && empty($fieldErrors) && !$form->getActions();

        $postedValues = [];
        foreach ($submission as $field) {
            $postedValues[$field->getHandle()] = $field->getValue();
        }

        $form->registerContext();

        $payload = [
            'success' => $success,
            'finished' => $form->isFinished(),
            'onSuccess' => $form->getSettings()->getBehavior()->successBehavior,
            'id' => $submission->getId(),
            'hash' => $form->getHash(),
            'values' => $postedValues,
            'errors' => $fieldErrors,
            'formErrors' => $form->getErrors(),
            'returnUrl' => $returnUrl,
            'submissionId' => $submission->id ?? null,
            'submissionToken' => $submission->token ?? null,
            'html' => $form->render(),
            'actions' => $form->getActions(),
            'multipage' => $form->isMultiPage(),
            'duplicate' => $form->isDuplicate(),
        ];

        $event = new PrepareAjaxResponsePayloadEvent($form, $payload);
        Event::trigger(Form::class, Form::EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD, $event);

        return $this->asJson($event->getPayload());
    }

    private function getFormFromRequest(): Form
    {
        $this->requirePostRequest();

        $request = \Craft::$app->getRequest();
        $isAjaxRequest = $request->getIsAjax();

        $formId = SessionContext::getPostedFormId();
        $form = $this->getFormsService()->getFormById($formId);
        if (!$form) {
            $message = \Craft::t('freeform', 'Form with ID {id} not found', ['id' => $formId]);

            if (!$isAjaxRequest) {
                throw new FreeformException($message);
            }

            $response = $this->asJson(['success' => false, 'message' => $message]);
            $response->setStatusCode(404);
            $response->send();

            exit;
        }

        return $form;
    }
}
