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

namespace Solspace\Freeform\Controllers;

use craft\db\Query;
use craft\db\Table;
use craft\helpers\ChartHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\DataObjects\PlanDetails;
use Solspace\Freeform\Library\Exceptions\Notifications\NotificationException;
use Solspace\Freeform\Library\Integrations\PaymentGateways\AbstractPaymentGatewayIntegration;
use Solspace\Freeform\Models\FieldModel;
use yii\base\Exception;
use yii\web\Response;

class ApiController extends BaseController
{
    public function actionForm(): Response
    {
        return \Craft::$app->runAction('freeform/submit');
    }

    public function actionFields(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        return $this->asJson($this->getFieldsService()->getAllFields(false));
    }

    public function actionNotifications(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        return $this->asJson($this->getNotificationsService()->getAllNotifications(false));
    }

    public function actionMailingLists(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $mailingLists = $this->getMailingListsService()->getAllIntegrationObjects();
        foreach ($mailingLists as $integration) {
            $integration->setForceUpdate(true);
        }

        return $this->asJson($mailingLists);
    }

    public function actionCrmIntegrations(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $crmIntegrations = $this->getCrmService()->getAllIntegrationObjects();
        foreach ($crmIntegrations as $integration) {
            $integration->setForceUpdate(true);

            try {
                $integration->getFields();
            } catch (\Exception $e) {
                return $this->asFailure($e->getMessage());
            }
        }

        return $this->asJson($crmIntegrations);
    }

    public function actionPaymentGateways(): Response
    {
        // TODO: add separate function to query single gateway?
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $paymentGateways = $this->getPaymentGatewaysService()->getAllIntegrationObjects();
        foreach ($paymentGateways as $integration) {
            $integration->setForceUpdate(true);
        }

        return $this->asJson($paymentGateways);
    }

    public function actionPaymentPlans(): Response
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $request = \Craft::$app->request;
        $errors = [];

        $integrationId = $request->post('integrationId');
        $name = $request->post('name');
        $amount = $request->post('amount');
        $currency = $request->post('currency');
        $interval = $request->post('interval');

        if (!$integrationId) {
            $errors[] = Freeform::t('IntegrationId is required');
        }

        if (!$name) {
            $errors[] = Freeform::t('Label is required');
        }

        if (!$amount) {
            $errors[] = Freeform::t('Amount is required');
        }

        /** @var AbstractPaymentGatewayIntegration $integration */
        $integration = $this->getPaymentGatewaysService()->getIntegrationObjectById($integrationId);

        if (empty($errors)) {
            $planDetails = new PlanDetails($name, $amount, $currency, $interval);
            $result = $integration->createPlan($planDetails);

            if ($result) {
                $integration->setForceUpdate(true);

                return $this->asJson(['success' => true, 'id' => $result]);
            }

            $error = $integration->getLastError();
            if ($error) {
                $error = $error->getPrevious();
                $errors[] = $error->getMessage();
            }
        }

        return $this->asJson(['success' => false, 'errors' => $errors]);
    }

    public function actionFormTemplates(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        return $this->asJson($this->getSettingsService()->getCustomFormTemplates());
    }

    public function actionQuickCreateField(): Response
    {
        $this->requirePostRequest();

        $request = \Craft::$app->request;
        $errors = [];

        $label = $request->post('label');
        $handle = $request->post('handle');
        $type = $request->post('type');

        if (!$label) {
            $errors[] = Freeform::t('Label is required');
        }

        if (!$handle) {
            $errors[] = Freeform::t('Handle is required');
        }

        $allowedFieldTypes = array_keys(AbstractField::getFieldTypes());
        if (!$type || !\in_array($type, $allowedFieldTypes, true)) {
            $errors[] = Freeform::t(
                'Type {type} is not allowed. Allowed types are ({allowedTypes})',
                [
                    'type' => $type,
                    'allowedTypes' => implode(', ', $allowedFieldTypes),
                ]
            );
        }

        $field = FieldModel::create();
        $field->label = $label;
        $field->handle = $handle;
        $field->type = $type;

        if (empty($errors) && $this->getFieldsService()->save($field)) {
            return $this->asJson(['success' => true]);
        }

        $fieldErrors = $field->getErrors();
        $errors = [];
        array_walk_recursive(
            $fieldErrors,
            function ($array) use (&$errors) {
                $errors[] = $array;
            }
        );

        return $this->asJson(['success' => false, 'errors' => $errors]);
    }

    public function actionQuickCreateNotification(): Response
    {
        $this->requirePostRequest();

        $request = \Craft::$app->request;
        $errors = [];

        $name = $request->post('name');

        if (!$name) {
            $errors[] = Freeform::t('Name is required');
        }

        try {
            $notification = $this->getNotificationsService()->create($name);
        } catch (NotificationException $exception) {
            $errors[] = $exception->getMessage();
        }

        if (empty($errors) && $notification) {
            $id = $notification->isFileBasedTemplate() ? $notification->filepath : $notification->id;

            return $this->asJson(['success' => true, 'id' => $id]);
        }

        return $this->asJson(['success' => false, 'errors' => $errors]);
    }

    public function actionGetSubmissionData(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SUBMISSIONS_ACCESS);

        // Required for Dashboard widget, unnecessary for Entries Index view
        $source = \Craft::$app->request->post('source');
        $formId = null;
        if ($source && str_starts_with($source, 'form:')) {
            $formId = (int) substr($source, 5);
        } elseif ('*' === $source) {
            $isAdmin = PermissionHelper::isAdmin();
            $manageAll = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);

            if (!$isAdmin && !$manageAll) {
                $formId = Freeform::getInstance()->submissions->getAllowedReadFormIds();
            }
        }

        $isSpam = \Craft::$app->request->post('isSpam');
        $startDateParam = \Craft::$app->request->post('startDate');
        $endDateParam = \Craft::$app->request->post('endDate');

        $startDate = DateTimeHelper::toDateTime($startDateParam, true);
        $endDate = DateTimeHelper::toDateTime($endDateParam, true);

        if (false === $startDate || false === $endDate) {
            throw new Exception('There was a problem calculating the start and end dates');
        }

        $endDate->modify('+1 day');
        $intervalUnit = ChartHelper::getRunChartIntervalUnit($startDate, $endDate);
        $submissions = Submission::TABLE;

        // Prep the query
        $query = (new Query())
            ->select(["COUNT({$submissions}.[[id]]) as [[value]]"])
            ->from($submissions)
        ;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
            $query->innerJoin(
                $elements,
                "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
            );
        }

        if ($formId) {
            $query->andWhere(["{$submissions}.[[formId]]" => $formId]);
        }
        if (null !== $isSpam) {
            $query->andWhere(["{$submissions}.[[isSpam]]" => $isSpam]);
        }

        // Get the chart data table
        $dataTable = $this->getRunChartDataFromQuery(
            $query,
            $startDate,
            $endDate,
            "{$submissions}.[[dateCreated]]",
            [
                'intervalUnit' => $intervalUnit,
                'valueLabel' => Freeform::t('Submissions'),
                'valueType' => 'number',
            ]
        );

        // Get the total submissions
        $total = 0;

        foreach ($dataTable['rows'] as $row) {
            $total += $row[1];
        }

        $formats = ChartHelper::formats();
        $formats['numberFormat'] = ',.0f';

        return $this->asJson(
            [
                'dataTable' => $dataTable,
                'total' => $total,
                'totalHtml' => $total,

                'formats' => $formats,
                'orientation' => \Craft::$app->locale->getOrientation(),
                'scale' => $intervalUnit,
            ]
        );
    }

    public function actionFinishTutorial(): Response
    {
        return $this->asJson(['success' => $this->getSettingsService()->finishTutorial()]);
    }

    public function actionOptionsFromSource(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_MANAGE);
        $this->requirePostRequest();
        $request = \Craft::$app->request;

        $source = $request->post('source');
        $target = $request->post('target');
        $configuration = $request->post('configuration');

        if (!\is_array($configuration)) {
            $configuration = [];
        }

        $options = $this->getFieldsService()->getOptionsFromSource($source, $target, $configuration);

        return $this->asJson(['data' => $options]);
    }

    private function getRunChartDataFromQuery(
        Query $query,
        \DateTime $startDate,
        \DateTime $endDate,
        string $dateColumn,
        array $options = []
    ): array {
        // Setup
        $options = array_merge(
            [
                'intervalUnit' => null,
                'categoryLabel' => Freeform::t('Date'),
                'valueLabel' => Freeform::t('Value'),
                'valueType' => 'number',
            ],
            $options
        );

        if ($options['intervalUnit'] && \in_array($options['intervalUnit'], ['year', 'month', 'day', 'hour'], true)) {
            $intervalUnit = $options['intervalUnit'];
        } else {
            $intervalUnit = ChartHelper::getRunChartIntervalUnit($startDate, $endDate);
        }

        // Prepare the query
        switch ($intervalUnit) {
            case 'year':
                $phpDateFormat = 'Y-01-01';

                break;

            case 'month':
                $phpDateFormat = 'Y-m-01';

                break;

            case 'day':
                $phpDateFormat = 'Y-m-d';

                break;

            case 'hour':
                $phpDateFormat = 'Y-m-d H:00:00';

                break;

            default:
                throw new Exception('Invalid interval unit: '.$intervalUnit);
        }

        // Assemble the data
        $rows = [];

        $cursorDate = clone $startDate;
        $endTimestamp = $endDate->getTimestamp();

        while ($cursorDate->getTimestamp() < $endTimestamp) {
            $cursorEndDate = clone $cursorDate;
            $cursorEndDate->modify('+1 '.$intervalUnit);
            $totalQuery = clone $query;
            $total = (float) $totalQuery
                ->andWhere(['>=', $dateColumn, Db::prepareDateForDb($cursorDate)])
                ->andWhere(['<', $dateColumn, Db::prepareDateForDb($cursorEndDate)])
                ->count('*')
            ;
            $rows[] = [$cursorDate->format($phpDateFormat), $total];
            $cursorDate = $cursorEndDate;
        }

        return [
            'columns' => [
                [
                    'type' => 'hour' === $intervalUnit ? 'datetime' : 'date',
                    'label' => $options['categoryLabel'],
                ],
                [
                    'type' => $options['valueType'],
                    'label' => $options['valueLabel'],
                ],
            ],
            'rows' => $rows,
        ];
    }
}
