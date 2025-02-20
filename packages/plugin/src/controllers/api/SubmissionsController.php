<?php

namespace Solspace\Freeform\controllers\api;

use craft\db\Query;
use craft\db\Table;
use craft\helpers\ChartHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use Solspace\Freeform\controllers\BaseController;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use yii\base\Exception;
use yii\web\Response;

class SubmissionsController extends BaseController
{
    public function actionGetSubmissionData(): Response
    {
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
            ->where(["{$submissions}.[[isHidden]]" => false])
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
