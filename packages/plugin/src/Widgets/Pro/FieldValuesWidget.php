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

namespace Solspace\Freeform\Widgets\Pro;

use craft\db\Query;
use craft\db\Table;
use craft\helpers\UrlHelper;
use Solspace\Commons\Helpers\ColorHelper;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Services\Pro\WidgetsService;
use Solspace\Freeform\Widgets\AbstractWidget;
use Solspace\Freeform\Widgets\ExtraWidgetInterface;

class FieldValuesWidget extends AbstractWidget implements ExtraWidgetInterface
{
    public ?string $title = null;

    public array|string|null $formId = null;

    public ?string $fieldId = null;

    public ?string $dateRange = null;

    public ?int $chartHeight = null;

    public ?string $chartType = null;

    public ?bool $showEmpty = null;

    public static function displayName(): string
    {
        return Freeform::getInstance()->name.' '.Freeform::t('Field Values');
    }

    public static function iconPath(): string
    {
        return __DIR__.'/../../icon-mask.svg';
    }

    public function init(): void
    {
        parent::init();

        if (null === $this->title) {
            $this->title = self::displayName();
        }

        if (null === $this->dateRange) {
            $this->dateRange = WidgetsService::RANGE_LAST_30_DAYS;
        }

        if (null === $this->chartHeight) {
            $this->chartHeight = 100;
        }

        if (null === $this->chartType) {
            $this->chartType = WidgetsService::CHART_POLAR_AREA;
        }

        if (null === $this->showEmpty) {
            $this->showEmpty = true;
        }
    }

    public function rules(): array
    {
        return [
            [['formId', 'fieldId'], 'required'],
        ];
    }

    public function getBodyHtml(): string
    {
        if (!Freeform::getInstance()->isPro()) {
            return Freeform::t(
                "Requires <a href='{link}'>Pro</a> edition",
                ['link' => UrlHelper::cpUrl('plugin-store/freeform')]
            );
        }

        $data = $this->getChartData();

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/radial-charts/body',
            [
                'chartData' => $data,
                'settings' => $this,
            ]
        );
    }

    public function getSettingsHtml(): string
    {
        $forms = $this->getFormService()->getAllForms();
        $formsOptions = [];
        foreach ($forms as $form) {
            $formsOptions[$form->id] = $form->name;
        }

        $fields = $this->getFieldService()->getAllFields();
        $fieldList = [];
        foreach ($fields as $field) {
            if (null === $field) {
                continue;
            }

            if (\in_array($field->type, [AbstractField::TYPE_TEXTAREA, AbstractField::TYPE_FILE], true)) {
                continue;
            }

            $fieldList[$field->id] = $field->label;
        }

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/radial-charts/settings',
            [
                'settings' => $this,
                'formOptions' => $formsOptions,
                'fieldList' => $fieldList,
                'dateRangeOptions' => $this->getWidgetsService()->getDateRanges(),
                'fieldValueSettings' => true,
                'chartTypes' => [
                    WidgetsService::CHART_PIE => 'Pie',
                    WidgetsService::CHART_DONUT => 'Donut',
                    WidgetsService::CHART_POLAR_AREA => 'Polar Area',
                ],
            ]
        );
    }

    private function getChartData(): array
    {
        $submissions = Submission::TABLE;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
        }

        $widgets = $this->getWidgetsService();

        [$rangeStart, $rangeEnd] = $widgets->getRange($this->dateRange);

        $formId = $this->formId;
        $fieldId = $this->fieldId;
        $form = $this->getFormService()->getFormById($formId);
        $field = $this->getFieldService()->getFieldById($fieldId);
        $columnName = Submission::generateFieldColumnName($fieldId, $field->handle);

        $contentTable = Submission::generateContentTableName($form->id, $form->handle);

        $showEmpty = $this->showEmpty;

        try {
            $query = (new Query())
                ->select(
                    [
                        "fc.[[{$columnName}]] as val",
                        'COUNT(fc.[[id]]) as count',
                    ]
                )
                ->from($contentTable.' fc')
                ->innerJoin($submissions.' s', 's.[[id]] = fc.[[id]]')
                ->where(['between', 's.[[dateCreated]]', $rangeStart, $rangeEnd])
                ->groupBy([$columnName])
            ;

            if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
                $query->innerJoin(
                    $elements,
                    "{$elements}.[[id]] = fc.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
                );
            }

            $result = $query->all();
        } catch (\Exception $e) {
            $result = [];
        }

        $cleanResults = [0 => ['val' => null, 'count' => 0]];
        foreach ($result as $item) {
            $value = $item['val'];
            if (!$value || '[]' === $value) {
                $cleanResults[0]['count'] += (int) $item['count'];
            } else {
                $cleanResults[] = $item;
            }
        }

        if (!$showEmpty) {
            unset($cleanResults[0]);
        }

        $labels = $data = $backgroundColors = $hoverBackgroundColors = [];
        foreach ($cleanResults as $item) {
            $columnValue = $item['val'];
            if ($columnValue && \in_array(
                $field->type,
                [AbstractField::TYPE_CHECKBOX_GROUP, AbstractField::TYPE_EMAIL],
                true
            )
            ) {
                $columnValue = implode(', ', json_decode($columnValue));
            }

            $count = (int) $item['count'];
            $color = $columnValue ? ColorHelper::getRGBColor($columnValue) : [5, 148, 209];

            $labels[] = $columnValue ?: 'Empty';
            $data[] = $count;
            $backgroundColors[] = sprintf('rgba(%s,0.8)', implode(',', $color));
            $hoverBackgroundColors[] = sprintf('rgba(%s,1)', implode(',', $color));
        }

        return [
            'type' => $this->chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => $backgroundColors,
                        'hoverBackgroundColor' => $hoverBackgroundColors,
                    ],
                ],
            ],
            'options' => [
                'tooltips' => [
                    'backgroundColor' => 'rgba(250, 250, 250, 0.9)',
                    'titleFontColor' => '#000',
                    'bodyFontColor' => '#000',
                    'cornerRadius' => 3,
                    'xPadding' => 10,
                    'yPadding' => 7,
                    'displayColors' => false,
                ],
                'responsive' => true,
                'legend' => [
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true,
                    ],
                ],
            ],
        ];
    }
}
