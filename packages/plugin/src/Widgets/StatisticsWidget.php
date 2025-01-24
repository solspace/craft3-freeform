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

namespace Solspace\Freeform\Widgets;

use craft\base\Widget;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\FreeformStatistics;
use Solspace\Freeform\Resources\Bundles\StatisticsWidgetBundle;

class StatisticsWidget extends Widget
{
    public array|string|null $statusIds = null;

    public array|string|null $formIds = null;

    public ?bool $showGlobalStatistics = null;

    public static function isSelectable(): bool
    {
        // This widget is only available to users that can manage submissions
        return PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
    }

    public static function displayName(): string
    {
        return Freeform::getInstance()->name.' '.Freeform::t('Statistics');
    }

    public static function iconPath(): string
    {
        return __DIR__.'/../icon-mask.svg';
    }

    public function init(): void
    {
        parent::init();

        if (!$this->statusIds) {
            $this->statusIds = '*';
        }

        if (!$this->formIds) {
            $this->formIds = '*';
        }

        if (!$this->showGlobalStatistics) {
            $this->showGlobalStatistics = false;
        }
    }

    public function rules(): array
    {
        return [
            [['statusIds', 'formIds'], 'required'],
        ];
    }

    public function getBodyHtml(): string
    {
        $freeform = Freeform::getInstance();
        $submissionsService = $freeform->submissions;
        $forms = $freeform->forms->getAllForms();

        $selectedStatusIds = $this->statusIds;
        if ('*' === $selectedStatusIds) {
            $selectedStatusIds = null;
        }

        $selectedFormIds = $this->formIds;
        if ('*' === $selectedFormIds) {
            $selectedFormIds = null;
        }

        $formStatistics = [];
        if (null !== $selectedFormIds) {
            foreach ($forms as $form) {
                if (\in_array($form->id, $selectedFormIds, false)) {
                    $submissionCount = $submissionsService->getSubmissionCount(
                        [$form->id],
                        $selectedStatusIds
                    );

                    $formStatistics[] = [
                        'label' => $form->name,
                        'statistics' => new FreeformStatistics($submissionCount, $form->spamBlockCount),
                    ];
                }
            }
        }

        if (empty($selectedFormIds)) {
            $submissionCount = $submissionsService->getSubmissionCount(null, $selectedStatusIds);
            $spamBlockCount = 0;
            foreach ($forms as $form) {
                if (!$form) {
                    continue;
                }
                $spamBlockCount += $form->spamBlockCount;
            }

            $formStatistics[] = [
                'statistics' => new FreeformStatistics($submissionCount, $spamBlockCount),
            ];
        }

        $fieldCount = null;
        $formCount = null;
        $notificationCount = null;
        if ($this->showGlobalStatistics) {
            $fieldCount = \count($freeform->fields->getAllFieldHandles());
            $formCount = \count($forms);
            $notificationCount = \count($freeform->notifications->getAllNotifications());
        }

        \Craft::$app->view->registerAssetBundle(StatisticsWidgetBundle::class);

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/statistics/body',
            [
                'formStatistics' => $formStatistics,
                'showGlobalStatistics' => $this->showGlobalStatistics,
                'fieldCount' => $fieldCount,
                'formCount' => $formCount,
                'notificationCount' => $notificationCount,
            ]
        );
    }

    public function getSettingsHtml(): string
    {
        $freeform = Freeform::getInstance();

        $statuses = $freeform->statuses->getAllStatuses();
        $statusList = [];
        foreach ($statuses as $status) {
            if (!$status) {
                continue;
            }
            $statusList[$status->id] = $status->name;
        }

        $forms = $freeform->forms->getAllForms();
        $formList = [];
        foreach ($forms as $form) {
            $formList[$form->id] = $form->name;
        }

        return \Craft::$app->view->renderTemplate(
            'freeform/_widgets/statistics/settings',
            [
                'settings' => $this->getSettings(),
                'statusList' => $statusList,
                'formList' => $formList,
            ]
        );
    }
}
