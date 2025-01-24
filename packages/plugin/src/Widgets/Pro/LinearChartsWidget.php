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

use craft\helpers\UrlHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Charts\LinearChartData;
use Solspace\Freeform\Services\Pro\WidgetsService;
use Solspace\Freeform\Widgets\AbstractWidget;
use Solspace\Freeform\Widgets\ExtraWidgetInterface;

class LinearChartsWidget extends AbstractWidget implements ExtraWidgetInterface
{
    public ?string $title = null;

    public array|string|null $formIds = null;

    public ?bool $aggregate = null;

    public ?string $dateRange = null;

    public ?int $chartHeight = null;

    public ?string $chartType = null;

    public static function displayName(): string
    {
        return Freeform::getInstance()->name.' '.Freeform::t('Linear Chart');
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

        if (null === $this->formIds) {
            $this->formIds = [];
        }

        if (null === $this->aggregate) {
            $this->aggregate = false;
        }

        if (null === $this->dateRange) {
            $this->dateRange = WidgetsService::RANGE_LAST_30_DAYS;
        }

        if (null === $this->chartHeight) {
            $this->chartHeight = 50;
        }

        if (null === $this->chartType) {
            $this->chartType = WidgetsService::CHART_LINE;
        }
    }

    public function rules(): array
    {
        return [
            [['formIds'], 'required'],
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

        $incrementSkip = match ($this->dateRange) {
            WidgetsService::RANGE_LAST_30_DAYS => 3,
            WidgetsService::RANGE_LAST_60_DAYS => 6,
            WidgetsService::RANGE_LAST_90_DAYS => 10,
            default => 1,
        };

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/linear-charts/body',
            [
                'chartData' => $data,
                'settings' => $this,
                'incrementSkip' => $incrementSkip,
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

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/linear-charts/settings',
            [
                'settings' => $this,
                'formOptions' => $formsOptions,
                'dateRangeOptions' => Freeform::getInstance()->widgets->getDateRanges(),
                'chartTypes' => [
                    WidgetsService::CHART_LINE => 'Line',
                    WidgetsService::CHART_BAR => 'Bar',
                ],
            ]
        );
    }

    private function getChartData(): LinearChartData
    {
        [$rangeStart, $rangeEnd] = $this->getWidgetsService()->getRange($this->dateRange);

        $formIds = $this->formIds;
        if ('*' === $formIds) {
            $formIds = array_keys($this->getFormService()->getAllForms());
        }

        $chartData = $this->getChartsService()->getLinearSubmissionChartData(
            $rangeStart,
            $rangeEnd,
            $formIds,
            (bool) $this->aggregate
        );

        $chartData->setChartType($this->chartType);

        return $chartData;
    }
}
