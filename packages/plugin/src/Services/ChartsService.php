<?php

namespace Solspace\Freeform\Services;

use Carbon\Carbon;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Charts\LinearChartData;
use Solspace\Freeform\Library\Charts\LinearItem;
use Solspace\Freeform\Library\Charts\RadialChartData;
use Solspace\Freeform\Library\Helpers\ColorHelper;

class ChartsService extends BaseService
{
    public function getMinimalSubmissionChartData(array $formIds): array
    {
        $submissions = Submission::TABLE;
        $elements = Table::ELEMENTS;

        $rangeStart = new Carbon('30 days ago', 'UTC');
        $rangeEnd = new Carbon('now', 'UTC');

        $datasets = [];
        foreach ($formIds as $formId) {
            $query = (new Query())
                ->select(["COUNT({$submissions}.[[id]]) as count", "DATE({$submissions}.[[dateCreated]]) as dt"])
                ->from(Submission::TABLE)
                ->where([
                    'between',
                    "{$submissions}.[[dateCreated]]",
                    $rangeStart->toDateTimeString(),
                    $rangeEnd->toDateTimeString(),
                ])
                ->andWhere([
                    "{$submissions}.[[formId]]" => $formId,
                    "{$submissions}.[[isHidden]]" => false,
                ])
                ->innerJoin(
                    $elements,
                    "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
                )
                ->orderBy(['dt' => \SORT_ASC])
                ->groupBy(['dt'])
                ->indexBy('dt')
            ;

            $result = $query->column();
            $refDate = $rangeStart->copy();

            $data = [];
            while ($refDate->lte($rangeEnd)) {
                $date = $refDate->format('Y-m-d');
                $data[] = ['uv' => $result[$date] ?? 0];

                $refDate->addDay();
            }

            $datasets[$formId] = $data;
        }

        return $datasets;
    }

    /**
     * @throws \Exception
     */
    public function getLinearSubmissionChartData(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $formIds,
        bool $aggregate = false
    ): LinearChartData {
        $submissions = Submission::TABLE;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
        }

        $diff = $rangeStart->diffInDays($rangeEnd);

        $labels = $dates = [];
        $dateContext = $rangeStart->copy();
        for ($i = 0; $i <= $diff; ++$i) {
            $labels[] = $dateContext->format('M j');
            $dates[] = $dateContext->format('Y-m-d');
            $dateContext->addDay();
        }

        $forms = Freeform::getInstance()->forms->getAllForms();
        $datasets = [];
        foreach ($formIds as $formId) {
            if (null !== $formId && !isset($forms[$formId])) {
                continue;
            }

            $query = (new Query())
                ->select(["DATE({$submissions}.[[dateCreated]]) as dt", "COUNT({$submissions}.[[id]]) as count"])
                ->from(Submission::TABLE)
                ->groupBy(['dt'])
            ;

            $query->where(
                [
                    'between',
                    "{$submissions}.[[dateCreated]]",
                    $rangeStart->toDateTimeString(),
                    $rangeEnd->toDateTimeString(),
                ]
            );

            $form = null;
            if ($aggregate) {
                $query->andWhere(['in', "{$submissions}.[[formId]]", $formIds]);
            } else {
                $form = $forms[$formId];
                $query->andWhere(["{$submissions}.[[formId]]" => $formId]);
            }

            if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
                $query->innerJoin(
                    $elements,
                    "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
                );
            }

            $result = $query->all();

            $data = [];
            foreach ($dates as $date) {
                $data[$date] = 0;
            }

            foreach ($result as $item) {
                $data[$item['dt']] = (int) $item['count'];
            }

            if ($form) {
                $color = ColorHelper::getRGBColor($form->color);
            } else {
                $color = [5, 148, 209];
            }

            $datasets[] = new LinearItem($form ? $form->name : 'Submissions', $color, $data);

            if ($aggregate) {
                break;
            }
        }

        return $this->getData($labels, $datasets);
    }

    /**
     * @param Form[] $forms
     */
    public function getRadialFormSubmissionData(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $forms
    ): RadialChartData {
        $formIds = array_keys($forms);

        $submissions = Submission::TABLE;
        $query = (new Query())
            ->select(["{$submissions}.[[formId]]", "COUNT({$submissions}.[[id]]) as count"])
            ->from($submissions)
            ->where(['between', "{$submissions}.[[dateCreated]]", $rangeStart, $rangeEnd])
            ->andWhere(['IN', "{$submissions}.[[formId]]", $formIds])
            ->groupBy(["{$submissions}.[[formId]]"])
        ;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
            $query->innerJoin(
                $elements,
                "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
            );
        }

        $result = $query->all();

        $labels = $data = $backgroundColors = $hoverBackgroundColors = $formsWithResults = [];
        foreach ($result as $item) {
            $formId = $item['formId'];
            $formsWithResults[] = $formId;

            $count = (int) $item['count'];
            $color = ColorHelper::getRGBColor($forms[$formId]->color);

            $labels[] = $forms[$formId]->name;
            $data[] = $count;
            $backgroundColors[] = \sprintf('rgba(%s,0.8)', implode(',', $color));
            $hoverBackgroundColors[] = \sprintf('rgba(%s,1)', implode(',', $color));
        }

        foreach ($formIds as $formId) {
            if (\in_array($formId, $formsWithResults, false)) {
                continue;
            }

            $color = ColorHelper::getRGBColor($forms[$formId]->color);

            $labels[] = $forms[$formId]->getName();
            $data[] = 0;
            $backgroundColors[] = \sprintf('rgba(%s,0.8)', implode(',', $color));
            $hoverBackgroundColors[] = \sprintf('rgba(%s,1)', implode(',', $color));
        }

        return (new RadialChartData())
            ->setLabels($labels)
            ->setData($data)
            ->setBackgroundColors($backgroundColors)
            ->setHoverBackgroundColors($hoverBackgroundColors)
        ;
    }

    /**
     * @throws \Exception
     */
    public function getData(array $labels, array $datasets): LinearChartData
    {
        $chartData = new LinearChartData();
        $chartData->setLabels($labels);
        $chartData->setDatasets($datasets);

        return $chartData;
    }

    /**
     * @return LinearChartData
     *
     * @throws \Exception
     */
    public function getStackedAreaChartData(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        array $formIds
    ): array {
        $submissions = Submission::TABLE;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
        }

        $forms = Freeform::getInstance()->forms->getAllForms();

        $formData = $formInfo = [];
        foreach ($formIds as $formId) {
            if (null !== $formId && !isset($forms[$formId])) {
                continue;
            }

            $form = $forms[$formId];

            $query = (new Query())
                ->select(["DATE({$submissions}.[[dateCreated]]) as dt", "COUNT({$submissions}.[[id]]) as count"])
                ->from(Submission::TABLE)
                ->groupBy(['dt'])
            ;

            $query
                ->where(Db::parseDateParam("{$submissions}.[[dateCreated]]", $rangeStart, '>='))
                ->andWhere(Db::parseDateParam("{$submissions}.[[dateCreated]]", $rangeEnd, '<='))
                ->andWhere(["{$submissions}.[[formId]]" => $formId])
                ->andWhere(["{$submissions}.[[isSpam]]" => false])
            ;

            if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
                $query->innerJoin(
                    $elements,
                    "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
                );
            }

            $result = $query->all();

            $data = [];
            foreach ($result as $item) {
                $data[$item['dt']] = (int) $item['count'];
            }

            $formData[$form->handle] = $data;

            $formInfo[] = [
                'name' => $form->name,
                'handle' => $form->handle,
                'color' => $form->color,
                'color_rgb' => ColorHelper::getRGBColor($form->color),
            ];
        }

        $diff = $rangeStart->diffInDays($rangeEnd);

        $chartData = [];
        $dateContext = $rangeStart->copy();
        for ($i = 0; $i <= $diff; ++$i) {
            $date = $dateContext->toDateString();
            $data = ['date' => $dateContext->format('Y-m-d')];
            foreach ($formData as $formHandle => $submissionCount) {
                $data['form_'.$formHandle] = $submissionCount[$date] ?? 0;
            }

            $chartData[] = $data;
            $dateContext->addDay();
        }

        return [
            'dataset' => $chartData,
            'info' => $formInfo,
        ];
    }

    public function getFakeStackedChartData(): array
    {
        $formInfo = [
            ['name' => 'Example Form 1', 'handle' => 'ex0', 'color' => '#ebebeb', 'color_rgb' => ColorHelper::getRGBColor('#ebebeb')],
            ['name' => 'Example Form 2', 'handle' => 'ex1', 'color' => '#f3f3f3', 'color_rgb' => ColorHelper::getRGBColor('#f3f3f3')],
            ['name' => 'Example Form 3', 'handle' => 'ex2', 'color' => '#e5e5e5', 'color_rgb' => ColorHelper::getRGBColor('#e5e5e5')],
        ];

        $data = [
            [79, 29, 10],
            [73, 25, 17],
            [42, 33, 33],
            [78, 50, 60],
            [67, 40, 52],
            [80, 43, 67],
        ];

        $date = (new Carbon())->subDays(\count($data));

        $chartData = [];
        foreach ($data as $set) {
            $row = ['date' => $date->addDay()->format('M j')];
            foreach ($set as $i => $col) {
                $row['form_ex'.$i] = $col;
            }
            $chartData[] = $row;
        }

        return [
            'dataset' => $chartData,
            'info' => $formInfo,
        ];
    }
}
