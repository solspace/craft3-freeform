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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Integrations\FetchCrmTypesEvent;
use Solspace\Freeform\Events\Integrations\PushEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Database\CRMHandlerInterface;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;
use Solspace\Freeform\Models\Pro\Payments\PaymentModel;
use Solspace\Freeform\Models\Pro\Payments\SubscriptionModel;
use Solspace\Freeform\Records\CrmFieldRecord;
use Solspace\Freeform\Records\IntegrationRecord;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CrmService extends AbstractIntegrationService implements CRMHandlerInterface
{
    /** @var array */
    private static $integrations;

    /** @var PaymentModel[]|SubscriptionModel[] */
    private $paymentAndSubscriptionCache = [];

    /**
     * Update the access token of an integration.
     *
     * @throws \Exception
     */
    public function updateAccessToken(AbstractCRMIntegration $integration)
    {
        $model = $this->getIntegrationById($integration->getId());
        $model->accessToken = $integration->getAccessToken();
        $model->settings = $integration->getSettings();

        $this->save($model);
    }

    /**
     * @throws \ReflectionException
     */
    public function getAllCRMServiceProviders(): array
    {
        if (null === self::$integrations) {
            $event = new FetchCrmTypesEvent();

            $this->trigger(self::EVENT_FETCH_TYPES, $event);
            $types = $event->getTypes();
            asort($types);

            self::$integrations = $types;
        }

        return self::$integrations;
    }

    /**
     * @throws \ReflectionException
     */
    public function getAllCRMSettingBlueprints(): array
    {
        $serviceProviderTypes = $this->getAllCRMServiceProviders();

        // Get all blueprints per class
        $settingBlueprints = [];

        /**
         * @var AbstractCRMIntegration $providerClass
         * @var string                 $name
         */
        foreach ($serviceProviderTypes as $providerClass => $name) {
            $settingBlueprints[$providerClass] = $providerClass::getSettingBlueprints();
        }

        return $settingBlueprints;
    }

    /**
     * Get all setting blueprints for a specific CRM integration.
     *
     * @param string $class
     *
     * @return SettingBlueprint[]
     *
     * @throws IntegrationException
     * @throws \ReflectionException
     */
    public function getCRMSettingBlueprints($class): array
    {
        $serviceProviderTypes = $this->getAllCRMServiceProviders();

        /**
         * @var AbstractCRMIntegration $providerClass
         */
        foreach ($serviceProviderTypes as $providerClass => $name) {
            if ($providerClass === $class) {
                return $providerClass::getSettingBlueprints();
            }
        }

        throw new IntegrationException('Could not get CRM settings');
    }

    public function getAllReadmes(): array
    {
        $dir = \Craft::getAlias('@freeform').'/Integrations/CRM/readmes/';
        $serviceProviderTypes = $this->getAllCRMServiceProviders();

        $readmes = [];

        /**
         * @var AbstractIntegration $providerClass
         * @var string              $name
         */
        foreach ($serviceProviderTypes as $providerClass => $name) {
            $filePath = $dir.$name.'.md';
            $content = null;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
            }
            $readmes[$providerClass] = $content;
        }

        return $readmes;
    }

    /**
     * Updates the fields of a given CRM integration.
     *
     * @param FieldObject[] $fields
     */
    public function updateFields(AbstractCRMIntegration $integration, array $fields): bool
    {
        $handles = [];
        foreach ($fields as $field) {
            $handles[] = $field->getHandle();
        }

        $id = $integration->getId();
        $existingFields = (new Query())
            ->select(['handle'])
            ->from(CrmFieldRecord::TABLE)
            ->where(['integrationId' => $id])
            ->column()
        ;

        $removableHandles = array_diff($existingFields, $handles);
        $addableHandles = array_diff($handles, $existingFields);
        $updatableHandles = array_intersect($handles, $existingFields);

        foreach ($removableHandles as $handle) {
            // PERFORM DELETE
            \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(
                    CrmFieldRecord::TABLE,
                    [
                        'integrationId' => $id,
                        'handle' => $handle,
                    ]
                )
                ->execute();
        }

        foreach ($fields as $field) {
            // PERFORM INSERT
            if (\in_array($field->getHandle(), $addableHandles, true)) {
                $record = new CrmFieldRecord();
                $record->integrationId = $id;
                $record->handle = $field->getHandle();
                $record->label = $field->getLabel();
                $record->type = $field->getType();
                $record->required = $field->isRequired();
                $record->save();
            }

            // PERFORM UPDATE
            if (\in_array($field->getHandle(), $updatableHandles, true)) {
                \Craft::$app
                    ->getDb()
                    ->createCommand()
                    ->update(
                        CrmFieldRecord::TABLE,
                        [
                            'label' => $field->getLabel(),
                            'type' => $field->getType(),
                            'required' => $field->isRequired() ? 1 : 0,
                        ],
                        [
                            'integrationId' => $id,
                            'handle' => $field->getHandle(),
                        ]
                    )
                    ->execute();
            }
        }

        // Remove ForceUpdate flag
        \Craft::$app
            ->getDb()
            ->createCommand()
            ->update(
                IntegrationRecord::TABLE,
                ['forceUpdate' => 0],
                ['id' => $id]
            )
            ->execute();

        return true;
    }

    /**
     * Returns all FieldObjects of a particular CRM integration.
     *
     * @return FieldObject[]
     */
    public function getFields(AbstractCRMIntegration $integration): array
    {
        $data = (new Query())
            ->select(['handle', 'label', 'type', 'required'])
            ->from(CrmFieldRecord::TABLE)
            ->where(['integrationId' => $integration->getId()])
            ->orderBy('label ASC')
            ->all()
        ;

        $fields = [];
        foreach ($data as $item) {
            $fields[] = new FieldObject(
                $item['handle'],
                $item['label'],
                $item['type'],
                $item['required']
            );
        }

        return $fields;
    }

    /**
     * Push the mapped object values to the CRM.
     *
     * @throws ComposerException
     */
    public function pushObject(Submission $submission): bool
    {
        $freeform = Freeform::getInstance();

        $form = $submission->getForm();
        $layout = $form->getLayout();
        $properties = $form->getIntegrationProperties();

        try {
            /** @var AbstractCRMIntegration $integration */
            $integration = $this->getIntegrationObjectById($properties->getIntegrationId());
        } catch (\Exception $e) {
            return false;
        }

        $logger = $freeform->logger->getLogger($integration->getServiceProvider());
        $mapping = $properties->getMapping();
        if (empty($mapping)) {
            $logger->warning(
                Freeform::t(
                    "No field mapping specified for '{integration}' integration",
                    ['integration' => $integration->getName()]
                )
            );

            return false;
        }

        /** @var FieldObject[] $crmFieldsByHandle */
        $crmFieldsByHandle = [];

        try {
            foreach ($integration->getFields() as $field) {
                $crmFieldsByHandle[$field->getHandle()] = $field;
            }
        } catch (RequestException $e) {
            $logger->error($e->getMessage());
        }

        $objectValues = [];
        $formFields = [];
        foreach ($mapping as $crmHandle => $fieldHandle) {
            try {
                $crmField = $crmFieldsByHandle[$crmHandle];
                $formField = $layout->getFieldByHandle($fieldHandle);

                $formFields[$crmHandle] = $formField;

                $objectValues[$crmHandle] = $integration->convertCustomFieldValue($crmField, $formField);
            } catch (\Exception $e) {
                try {
                    $objectValues[$crmHandle] = $this->getExtraFieldsValue($fieldHandle, $submission, $integration);
                } catch (FreeformException $exception) {
                    $logger->warning($exception->getMessage());
                } catch (\Exception $exception) {
                    $logger->error($exception->getMessage());
                }
            }
        }

        [$isValid, $objectValues] = $this->onBeforePush($integration, $objectValues);
        if (!$isValid) {
            return false;
        }

        if (!empty($objectValues)) {
            try {
                $result = $integration->pushObject($objectValues, $formFields);

                if ($result) {
                    $this->onAfterPush($integration, $objectValues);
                }

                return $result;
            } catch (\Exception $e) {
                if ($e instanceof ClientException && $e->getResponse()) {
                    $logger->error($e->getResponse()->getBody());
                } else {
                    $logger->error($e->getMessage());
                }
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function getIntegrationType(): string
    {
        return IntegrationRecord::TYPE_CRM;
    }

    /**
     * @return array - [isValid, values]
     */
    private function onBeforePush(AbstractIntegration $integration, array $values): array
    {
        $event = new PushEvent($integration, $values);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);

        return [$event->isValid, $event->getValues()];
    }

    private function onAfterPush(AbstractIntegration $integration, array $values): bool
    {
        $event = new PushEvent($integration, $values);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->isValid;
    }

    /**
     * @return null|mixed
     *
     * @throws FreeformException
     */
    private function getExtraFieldsValue(string $handle, Submission $submission, AbstractIntegration $integration)
    {
        if (!preg_match('/^(\w+)###(.*)$/', $handle, $matches)) {
            throw new FreeformException(
                Freeform::t(
                    'Cannot access field "{handle}" for "{integration}" integration',
                    [
                        'handle' => $handle,
                        'integration' => $integration->getName(),
                    ]
                )
            );
        }

        [$_, $object, $property] = $matches;

        switch ($object) {
            case 'payments':
                $targetObject = $this->getPaymentOrSubscription($submission);

                break;

            default:
                $targetObject = null;
        }

        if ($targetObject) {
            static $accessor;

            if (null === $accessor) {
                $accessor = PropertyAccess::createPropertyAccessor();
            }

            if ($accessor->isReadable($targetObject, $property)) {
                return $accessor->getValue($targetObject, $property);
            }
        }

        throw new FreeformException(
            Freeform::t(
                'Cannot access property "{property}" on "{object}" for "{integration}" integration',
                [
                    'property' => $property,
                    'object' => $object,
                    'integration' => $integration->getName(),
                ]
            )
        );
    }

    /**
     * @return null|PaymentModel|SubscriptionModel
     */
    private function getPaymentOrSubscription(Submission $submission)
    {
        $submissionId = $submission->id;
        if (!isset($this->paymentAndSubscriptionCache[$submissionId])) {
            if (!Freeform::getInstance()->isPro()) {
                $this->paymentAndSubscriptionCache[$submissionId] = null;
            } else {
                $payment = Freeform::getInstance()->payments->getBySubmissionId($submissionId);
                if ($payment) {
                    $this->paymentAndSubscriptionCache[$submissionId] = $payment;
                } else {
                    $subscription = Freeform::getInstance()->subscriptions->getBySubmissionId($submissionId);
                    if ($subscription) {
                        $this->paymentAndSubscriptionCache[$submissionId] = $subscription;
                    } else {
                        $this->paymentAndSubscriptionCache[$submissionId] = null;
                    }
                }
            }
        }

        return $this->paymentAndSubscriptionCache[$submissionId];
    }
}
