<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Services;

use craft\db\Query;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Fields\Pro\Payments\CreditCardDetailsField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\PaymentInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Composer\Components\Properties\PaymentProperties;
use Solspace\Freeform\Library\DataObjects\CustomerDetails;
use Solspace\Freeform\Library\DataObjects\PaymentDetails;
use Solspace\Freeform\Library\DataObjects\SubscriptionDetails;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationNotFoundException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\PaymentGateways\AbstractPaymentGatewayIntegration;
use Solspace\Freeform\Library\Integrations\PaymentGateways\PaymentGatewayIntegrationInterface;
use Solspace\Freeform\Models\IntegrationModel;
use Solspace\Freeform\Records\IntegrationRecord;
use yii\base\Event;

class IntegrationsService extends BaseService
{
    /**
     * @return IntegrationModel[]
     *
     * @throws \Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException
     */
    public function getAllIntegrations(): array
    {
        $results = $this->getQuery()->all();

        $models = [];
        foreach ($results as $result) {
            $model = $this->createIntegrationModel($result);

            try {
                $model->getIntegrationObject();
                $models[] = $model;
            } catch (IntegrationNotFoundException $e) {
            }
        }

        return $models;
    }

    /**
     * Pushes all emails to their respective mailing lists, if applicable
     * Does nothing otherwise.
     *
     * @param AbstractField[] $fields
     */
    public function pushToMailingLists(Submission $submission, array $fields)
    {
        $form = $submission->getForm();

        if (!Freeform::getInstance()->isPro() || $form->getSuppressors()->isApi()) {
            return;
        }

        $mailingListHandler = Freeform::getInstance()->mailingLists;

        foreach ($fields as $field) {
            try {
                $emailField = $form->getLayout()->getFieldByHash($field->getEmailFieldHash());

                // TODO: Log any errors that happen
                $integration = $mailingListHandler->getIntegrationObjectById($field->getIntegrationId());
                $mailingList = $mailingListHandler->getListById($integration, $field->getResourceId());

                /** @var FieldObject[] $mailingListFieldsByHandle */
                $mailingListFieldsByHandle = [];
                foreach ($mailingList->getFields() as $mailingListField) {
                    $mailingListFieldsByHandle[$mailingListField->getHandle()] = $mailingListField;
                }

                $emailList = $submission->{$emailField->getHandle()}->getRecipients();
                if ($emailList) {
                    $mappedValues = [];
                    if ($field->getMapping()) {
                        foreach ($field->getMapping() as $key => $handle) {
                            if (!isset($mailingListFieldsByHandle[$key], $submission->{$handle})) {
                                continue;
                            }

                            $mailingListField = $mailingListFieldsByHandle[$key];

                            $convertedValue = $integration->convertCustomFieldValue(
                                $mailingListField,
                                $submission->{$handle}
                            );

                            $mappedValues[$key] = $convertedValue;
                        }
                    }

                    $mailingList->pushEmailsToList($emailList, $mappedValues);
                    $mailingListHandler->flagIntegrationForUpdating($integration);
                }
            } catch (FreeformException $exception) {
                continue;
            }
        }
    }

    /**
     * Send out any email notifications.
     */
    public function sendOutEmailNotifications(Form $form, Submission $submission)
    {
        $mailer = Freeform::getInstance()->mailer;
        $fields = $form->getLayout()->getFields();

        $event = new SendNotificationsEvent($form, $submission, $mailer, $fields);
        Event::trigger(Form::class, Form::EVENT_SEND_NOTIFICATIONS, $event);
    }

    /**
     * Push the submitted data to the mapped fields of a CRM integration.
     */
    public function pushToCRM(Submission $submission)
    {
        $form = $submission->getForm();

        if (!Freeform::getInstance()->isPro() || $form->getSuppressors()->isApi()) {
            return;
        }

        Freeform::getInstance()->crm->pushObject($submission);
    }

    /**
     * Makes all payment related processing of the submission, like making payments, creating subscriptions etc.
     *
     * @param Submission $submission saved submission
     *
     * @return bool
     */
    public function processPayments(Submission $submission)
    {
        if (!Freeform::getInstance()->isPro()) {
            return true;
        }

        $form = $submission->getForm();
        $paymentFields = $form->getLayout()->getFields(PaymentInterface::class);
        if (!$paymentFields || 0 === \count($paymentFields) || $form->getSuppressors()->isPayments()) {
            return true; // no payment fields, so no processing needed
        }

        $paymentField = reset($paymentFields);
        $token = $paymentField->getValue();
        if ($form->isGraphQLPosted() && !$token) {
            return true; // no payment field value, so no processing needed
        }

        // atm we support only single payment field

        if (!$submission->getId()) {
            // TODO: add to string constants? translate?
            $submission->addError($submission->getFieldColumnName($paymentFields[0]->getId()), 'Can\'t process payments for unsaved submission!');
            $paymentFields[0]->addError('Can\'t process payments for unsaved submission!');

            return false;
        }

        $paymentGatewayHandler = Freeform::getInstance()->paymentGateways;
        $properties = $form->getPaymentProperties();

        foreach ($paymentFields as $field) {
            /** @var PaymentGatewayIntegrationInterface $integration */
            $integration = $paymentGatewayHandler->getIntegrationObjectById($properties->getIntegrationId());

            /** @var CreditCardDetailsField $field */
            $field = $submission->{$field->getHandle()};

            $paymentType = $properties->getPaymentType();
            $paymentFieldMapping = $properties->getPaymentFieldMapping();
            $customerFieldMapping = $properties->getCustomerFieldMapping();
            $dynamicValues = [];

            if (\is_array($paymentFieldMapping)) {
                foreach ($paymentFieldMapping as $key => $handle) {
                    $value = $submission->{$handle}->getValue();
                    if ($value) {
                        $dynamicValues[$key] = $value;
                    }
                }
            }

            if (\is_array($customerFieldMapping)) {
                foreach ($customerFieldMapping as $key => $handle) {
                    $value = $submission->{$handle}->getValue();
                    if ($value) {
                        $dynamicValues[$key] = $value;
                    }
                }
            }

            $token = $field->getValue();

            $result = false;

            switch ($paymentType) {
                case PaymentProperties::PAYMENT_TYPE_SINGLE:
                    $customer = CustomerDetails::fromArray($dynamicValues);
                    $paymentDetails = new PaymentDetails($token, $submission, $customer);
                    $result = $integration->processPayment($paymentDetails, $properties);

                    break;

                case PaymentProperties::PAYMENT_TYPE_PREDEFINED_SUBSCRIPTION:
                case PaymentProperties::PAYMENT_TYPE_DYNAMIC_SUBSCRIPTION:
                    $subscriptionDetails = new SubscriptionDetails($token, $submission);
                    $result = $integration->processSubscription($subscriptionDetails, $properties);

                    break;
            }

            if (false === $result) {
                $this->applyPaymentErrors($submission, $integration);

                return false;
            }
        }

        return true;
    }

    /**
     * Gets last error from integration and adds it to submission element.
     *
     * @param Submission                        $submission
     * @param AbstractPaymentGatewayIntegration $integration
     */
    protected function applyPaymentErrors($submission, $integration)
    {
        $error = $integration->getLastError();
        $submission->addError($error->getMessage());

        $settings = $integration->getSettings();
        $suppress = $settings[Stripe::SETTING_SUPPRESS_ON_FAIL] ?? false;

        if ((bool) $suppress) {
            $submission->getForm()->enableSuppression();
        }
    }

    protected function getQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'integration.id',
                    'integration.name',
                    'integration.handle',
                    'integration.type',
                    'integration.class',
                    'integration.accessToken',
                    'integration.settings',
                    'integration.forceUpdate',
                    'integration.lastUpdate',
                ]
            )
            ->from(IntegrationRecord::TABLE.' integration')
            ->orderBy(['id' => \SORT_ASC])
        ;
    }

    protected function createIntegrationModel(array $data): IntegrationModel
    {
        $model = new IntegrationModel($data);

        $model->lastUpdate = new \DateTime($model->lastUpdate);
        $model->forceUpdate = (bool) $model->forceUpdate;
        $model->settings = $model->settings ? json_decode($model->settings, true) : [];

        return $model;
    }
}
