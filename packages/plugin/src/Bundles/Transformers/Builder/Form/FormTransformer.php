<?php

namespace Solspace\Freeform\Bundles\Transformers\Builder\Form;

use Carbon\Carbon;
use Solspace\Freeform\Events\Forms\GenerateLinksEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Services\ChartsService;
use Solspace\Freeform\Services\Form\FieldsService;
use Solspace\Freeform\Services\Form\LayoutsService;
use Solspace\Freeform\Services\Form\TranslationsService;
use Solspace\Freeform\Services\SubmissionsService;
use yii\base\Event;

class FormTransformer
{
    public const EVENT_ATTACH_LINKS = 'attach-links';

    public function __construct(
        private FieldsService $fieldsService,
        private LayoutsService $layoutsService,
        private TranslationsService $translationsService,
        private FieldTransformer $fieldTransformer,
        private LayoutTransformer $layoutTransformer,
        private ChartsService $chartsService,
        private SubmissionsService $submissionsService,
    ) {}

    public function transformList(array $forms): array
    {
        $forms = array_values(
            array_filter(
                $forms,
                [$this, 'checkPermissions']
            )
        );

        $transformed = array_map(
            [$this, 'transformBasic'],
            $forms
        );

        $transformed = $this->decorateWithSubmissionStatistics($transformed);

        return $this->attachLinks($forms, $transformed);
    }

    public function checkPermissions(Form $form): ?Form
    {
        $canManageForm = PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_MANAGE);
        if (!$canManageForm) {
            $canManageForm = PermissionHelper::checkPermission(
                PermissionHelper::prepareNestedPermission(
                    Freeform::PERMISSION_FORMS_MANAGE,
                    $form->getId()
                )
            );
        }

        $canReadSubmissions = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_READ);
        if (!$canReadSubmissions) {
            $canReadSubmissions = PermissionHelper::checkPermission(
                PermissionHelper::prepareNestedPermission(
                    Freeform::PERMISSION_SUBMISSIONS_READ,
                    $form->getId()
                )
            );
        }

        $canManageSubmissions = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
        if (!$canManageSubmissions) {
            $canManageSubmissions = PermissionHelper::checkPermission(
                PermissionHelper::prepareNestedPermission(
                    Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                    $form->getId()
                )
            );
        }

        if ($canManageForm || $canReadSubmissions || $canManageSubmissions) {
            return $form;
        }

        return null;
    }

    public function transform(Form $form): object
    {
        $fields = $this->fieldsService->getFields($form);

        $transformed = $this->transformBasic($form);
        $transformed->translations = $this->translationsService->getFormTranslations($form);
        $transformed->layout = (object) [
            'fields' => array_map([$this->fieldTransformer, 'transform'], $fields),
            'pages' => array_map(
                [$this->layoutTransformer, 'transformPage'],
                $this->layoutsService->getPages($form),
            ),
            'layouts' => array_map(
                [$this->layoutTransformer, 'transformLayout'],
                $this->layoutsService->getLayouts($form)
            ),
            'rows' => array_map(
                [$this->layoutTransformer, 'transformRow'],
                $this->layoutsService->getRows($form)
            ),
        ];

        return $transformed;
    }

    private function transformBasic(Form $form): object
    {
        $typeClass = $form::class;
        $settings = $form->getSettings();

        // Only forms made in the last hour are considered new
        $isNew = $form->getDateCreated()->greaterThanOrEqualTo(Carbon::now()->subHour());

        return (object) [
            'id' => $form->getId(),
            'uid' => $form->getUid(),
            'type' => $typeClass,
            'name' => $form->getName(),
            'handle' => $form->getHandle(),
            'description' => $form->getDescription(),
            'settings' => $settings->toArray(),
            'ownership' => $this->getOwnership($form),
            'isNew' => $isNew,
            'dateArchived' => $form->getDateArchived(),
        ];
    }

    private function decorateWithSubmissionStatistics(array $forms): array
    {
        $formIds = array_map(fn ($form) => $form->id, $forms);

        $chartData = $this->chartsService->getMinimalSubmissionChartData($formIds);
        $submissions = $this->submissionsService->getSubmissionCountByForm();
        $spamSubmissions = $this->submissionsService->getSubmissionCountByForm(true);
        $savedSubmissions = $this->submissionsService->getSavedSubmissionCountByForm();

        foreach ($forms as $form) {
            $form->chartData = $chartData[$form->id] ?? [];
            $form->counters = [
                'submissions' => $submissions[$form->id] ?? 0,
                'spam' => $spamSubmissions[$form->id] ?? 0,
                'saved' => $savedSubmissions[$form->id] ?? 0,
            ];
        }

        return $forms;
    }

    private function getOwnership(Form $form): array
    {
        $createdByUserUrl = null;
        $updatedByUserUrl = null;
        $currentUser = \Craft::$app->getUser()->getIdentity();
        $formatter = \Craft::$app->getFormatter();
        $ownership = [
            'created' => [
                'datetime' => $formatter->asDatetime(Carbon::parse($form->getDateCreated(), $formatter->timeZone)->toDateTimeLocalString(), 'short'),
            ],
            'updated' => [
                'datetime' => $formatter->asDatetime(Carbon::parse($form->getDateUpdated(), $formatter->timeZone)->toDateTimeLocalString(), 'short'),
            ],
        ];

        if (null !== $form->getCreatedBy()) {
            if ($currentUser->id === $form->getCreatedBy()->id || $currentUser->can('editUsers')) {
                $createdByUserUrl = $form->getCreatedBy()->cpEditUrl;
            }

            $ownership['created']['user'] = [
                'id' => $form->getCreatedBy()->getId(),
                'url' => $createdByUserUrl,
                'name' => $form->getCreatedBy()->name,
            ];
        }

        if (null !== $form->getUpdatedBy()) {
            if ($currentUser->id === $form->getUpdatedBy()->id || $currentUser->can('editUsers')) {
                $updatedByUserUrl = $form->getUpdatedBy()->cpEditUrl;
            }

            $ownership['updated']['user'] = [
                'id' => $form->getUpdatedBy()->getId(),
                'url' => $updatedByUserUrl,
                'name' => $form->getUpdatedBy()->name,
            ];
        }

        return $ownership;
    }

    private function attachLinks(array $forms, array $transformed): array
    {
        foreach ($transformed as $data) {
            $form = array_filter($forms, fn (Form $form) => $form->getId() === $data->id);
            $form = reset($form) ?? new \stdClass();

            $event = new GenerateLinksEvent($form, $data);
            Event::trigger($this, self::EVENT_ATTACH_LINKS, $event);

            $data->links = $event->getLinks();
        }

        return $transformed;
    }
}
