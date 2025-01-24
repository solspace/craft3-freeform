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
use Solspace\Freeform\Events\Integrations\FetchPaymentGatewayTypesEvent;
use Solspace\Freeform\Library\Database\PaymentGatewayHandlerInterface;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\PaymentGateways\AbstractPaymentGatewayIntegration;
use Solspace\Freeform\Library\Integrations\PaymentGateways\DataObjects\PlanObject;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;
use Solspace\Freeform\Models\IntegrationModel;
use Solspace\Freeform\Records\IntegrationRecord;
use Solspace\Freeform\Records\Pro\Payments\SubscriptionPlanRecord;

// TODO: move to payments module?

class PaymentGatewaysService extends AbstractIntegrationService implements PaymentGatewayHandlerInterface
{
    /** @var array */
    private static $integrations;

    /**
     * @return AbstractPaymentGatewayIntegration
     */
    public function getAllPaymentGatewayServiceProviders(): array
    {
        if (null === self::$integrations) {
            $event = new FetchPaymentGatewayTypesEvent();
            $this->trigger(self::EVENT_FETCH_TYPES, $event);

            self::$integrations = $event->getTypes();
        }

        return self::$integrations;
    }

    public function getAllPaymentGatewaySettingBlueprints(): array
    {
        $serviceProviderTypes = $this->getAllPaymentGatewayServiceProviders();

        // Get all blueprints per class
        $settingBlueprints = [];

        /**
         * @var AbstractIntegration $providerClass
         * @var string              $name
         */
        foreach ($serviceProviderTypes as $providerClass => $name) {
            $settingBlueprints[$providerClass] = $providerClass::getSettingBlueprints();
        }

        return $settingBlueprints;
    }

    public function getAllReadmes(): array
    {
        $dir = \Craft::getAlias('@freeform').'/Integrations/PaymentGateways/readmes/';
        $serviceProviderTypes = $this->getAllPaymentGatewayServiceProviders();

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
     * Get all setting blueprints for a specific mailing list integration.
     *
     * @param string $class
     *
     * @return SettingBlueprint[]
     *
     * @throws IntegrationException
     */
    public function getPaymentGatewaySettingBlueprints($class): array
    {
        $serviceProviderTypes = $this->getAllPaymentGatewayServiceProviders();

        /**
         * @var AbstractIntegration $providerClass
         * @var string              $name
         */
        foreach ($serviceProviderTypes as $providerClass => $name) {
            if ($providerClass === $class) {
                return $providerClass::getSettingBlueprints();
            }
        }

        throw new IntegrationException('Could not get Payment Gateway settings');
    }

    /**
     * Updates the fields of a given CRM integration.
     *
     * @param PlanObject[] $plans
     */
    public function updatePlans(AbstractPaymentGatewayIntegration $integration, array $plans): bool
    {
        $handles = [];
        foreach ($plans as $plan) {
            $handles[] = $plan->getId();
        }

        $id = $integration->getId();
        $existingPlans = (new Query())
            ->select(['resourceId'])
            ->from(SubscriptionPlanRecord::TABLE)
            ->where(['integrationId' => $id])
            ->column()
        ;

        $removableHandles = array_diff($existingPlans, $handles);
        $addableHandles = array_diff($handles, $existingPlans);
        $updatableHandles = array_intersect($handles, $existingPlans);

        foreach ($removableHandles as $handle) {
            // PERFORM DELETE
            \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(
                    SubscriptionPlanRecord::TABLE,
                    [
                        'integrationId' => $id,
                        'resourceId' => $handle,
                    ]
                )
                ->execute();
        }

        foreach ($plans as $plan) {
            // PERFORM INSERT
            if (\in_array($plan->getId(), $addableHandles, true)) {
                $record = new SubscriptionPlanRecord();
                $record->integrationId = $id;
                $record->resourceId = $plan->getId();
                $record->name = $plan->getName();
                $record->save();
            }

            // PERFORM UPDATE
            if (\in_array($plan->getId(), $updatableHandles, true)) {
                \Craft::$app
                    ->getDb()
                    ->createCommand()
                    ->update(
                        SubscriptionPlanRecord::TABLE,
                        [
                            'name' => $plan->getName(),
                        ],
                        [
                            'integrationId' => $id,
                            'resourceId' => $plan->getId(),
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
     * Returns all ListObject of a particular Payment Gateway integration.
     *
     * @return SubscriptionPlanInterface[]
     */
    public function getPlans(AbstractPaymentGatewayIntegration $integration): array
    {
        $integration->setForceUpdate($this->isForceUpdate());

        return $integration->fetchPlans();
    }

    /**
     * {@inheritdoc}integrations
     */
    protected function getIntegrationType(): string
    {
        return IntegrationRecord::TYPE_PAYMENT_GATEWAY;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterSaveHandler(IntegrationModel $model)
    {
        try {
            if ($model->getIntegrationObject()->checkConnection()) {
                /** @var AbstractPaymentGatewayIntegration $paymentGateway */
                $paymentGateway = $model->getIntegrationObject();
                $paymentGateway->setForceUpdate(true);
                $paymentGateway->getPlans();
            }
        } catch (IntegrationException $e) {
            \Craft::$app->session->setError($e->getMessage());
        }
    }
}
