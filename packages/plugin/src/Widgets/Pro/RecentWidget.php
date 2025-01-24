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
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Widgets\AbstractWidget;
use Solspace\Freeform\Widgets\ExtraWidgetInterface;

class RecentWidget extends AbstractWidget implements ExtraWidgetInterface
{
    public const DEFAULT_LIMIT = 5;

    public ?string $title = null;

    public array|string|null $formIds = null;

    public ?int $limit = null;

    public static function displayName(): string
    {
        return Freeform::getInstance()->name.' '.Freeform::t('Recent');
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

        if (null === $this->limit) {
            $this->limit = self::DEFAULT_LIMIT;
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

        $forms = $this->getFormService()->getAllForms();
        $formIdList = $this->formIds;
        if ('*' === $formIdList) {
            $formIdList = array_keys($forms);
        }

        $submissions = Submission::find()
            ->formId($formIdList)
            ->orderBy(['id' => \SORT_DESC])
            ->limit((int) $this->limit)
        ;

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/recent/body',
            [
                'submissions' => $submissions,
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

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/recent/settings',
            [
                'settings' => $this,
                'formOptions' => $formsOptions,
                'dateRangeOptions' => $this->getWidgetsService()->getDateRanges(),
            ]
        );
    }
}
