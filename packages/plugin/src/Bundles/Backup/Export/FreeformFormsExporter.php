<?php

namespace Solspace\Freeform\Bundles\Backup\Export;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Backup\BatchProcessing\ElementQueryProcessor;
use Solspace\Freeform\Bundles\Backup\Collections\FieldCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormSubmissionCollection;
use Solspace\Freeform\Bundles\Backup\Collections\IntegrationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\NotificationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\NotificationTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\PageCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RowCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RulesCollection;
use Solspace\Freeform\Bundles\Backup\DTO\Field;
use Solspace\Freeform\Bundles\Backup\DTO\Form;
use Solspace\Freeform\Bundles\Backup\DTO\FormSubmissions;
use Solspace\Freeform\Bundles\Backup\DTO\ImportPreview;
use Solspace\Freeform\Bundles\Backup\DTO\Integration;
use Solspace\Freeform\Bundles\Backup\DTO\Layout;
use Solspace\Freeform\Bundles\Backup\DTO\Notification;
use Solspace\Freeform\Bundles\Backup\DTO\NotificationTemplate;
use Solspace\Freeform\Bundles\Backup\DTO\Page;
use Solspace\Freeform\Bundles\Backup\DTO\Row;
use Solspace\Freeform\Bundles\Backup\DTO\Rule;
use Solspace\Freeform\Bundles\Backup\DTO\RuleCondition;
use Solspace\Freeform\Bundles\Backup\DTO\Submission;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\Bundles\Rules\RuleProvider;
use Solspace\Freeform\Elements\Submission as FFSubmission;
use Solspace\Freeform\Fields\Implementations\Pro\GroupField;
use Solspace\Freeform\Form\Form as FreeformForm;
use Solspace\Freeform\Form\Layout\Layout as FreeformLayout;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Library\Helpers\StringHelper as FreeformStringHelper;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Rules\RuleInterface;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\Form\FormFieldRecord;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\Integrations\IntegrationsService;

class FreeformFormsExporter extends BaseExporter
{
    public function __construct(
        private NotificationsProvider $notificationsProvider,
        private PropertyProvider $propertyProvider,
        private RuleProvider $ruleProvider,
        private FormsService $forms,
        private IntegrationsService $integrations,
    ) {}

    public function collectDataPreview(): ImportPreview
    {
        $preview = new ImportPreview();

        $preview->forms = $this->collectForms();
        $preview->notificationTemplates = $this->collectNotifications();

        $uidToNameMap = [];
        foreach ($preview->forms as $form) {
            $uidToNameMap[$form->uid] = $form->name;
        }

        $integrations = $this->integrations->getAllIntegrations();
        $preview->integrations = new IntegrationCollection();
        foreach ($integrations as $integration) {
            $dto = new Integration();
            $dto->name = $integration->name;
            $dto->uid = $integration->uid;
            $dto->icon = $integration->getIntegrationObject()->getTypeDefinition()->getIconUrl();

            $preview->integrations->add($dto);
        }

        $table = FFSubmission::TABLE;

        $submissions = FFSubmission::find()
            ->select("COUNT({$table}.[[id]]) as count")
            ->innerJoin(FormRecord::TABLE.' f', "[[f]].[[id]] = {$table}.[[formId]]")
            ->groupBy("{$table}.[[formId]]")
            ->indexBy('[[f]].uid')
            ->column()
        ;

        $formSubmissions = [];
        foreach ($submissions as $uid => $count) {
            $formSubmissions[] = [
                'form' => [
                    'uid' => $uid,
                    'name' => $uidToNameMap[$uid],
                ],
                'count' => $count,
            ];
        }

        $preview->formSubmissions = $formSubmissions;

        return $preview;
    }

    protected function collectForms(?array $ids = null): FormCollection
    {
        $collection = new FormCollection();

        $query = $this->forms->getFormQuery();
        if (null !== $ids) {
            $query->where(['uid' => $ids]);
        }

        $forms = $this->forms->getFormsFromQuery($query);

        /**
         * @var FreeformForm $form
         */
        foreach ($forms as $index => $form) {
            /** @var FormFieldRecord[] $formFieldRecords */
            $formFieldRecords = FormFieldRecord::find()
                ->where(['formId' => $form->getId()])
                ->indexBy('uid')
                ->all()
            ;

            $exported = new Form();
            $exported->uid = $form->getUid();
            $exported->name = $form->getName();
            $exported->handle = $form->getHandle();
            $exported->order = $index;
            $exported->settings = $form->getSettings();

            $exported->rules = $this->collectRules($form);
            $exported->notifications = new NotificationCollection();

            $notifications = $this->notificationsProvider->getRecordsByForm($form);
            foreach ($notifications as $notification) {
                $metadata = json_decode($notification->metadata, true);

                $exportNotification = new Notification();
                $exportNotification->id = $notification->id;
                $exportNotification->uid = $notification->uid;
                $exportNotification->idAttribute = 'template';
                $exportNotification->name = $metadata['name'] ?? 'Admin Notification';
                $exportNotification->type = $notification->class;
                $exportNotification->metadata = $metadata;

                $exported->notifications->add($exportNotification);
            }

            $exported->pages = new PageCollection();

            foreach ($form->getLayout()->getPages() as $page) {
                if (null === $page->getUid()) {
                    continue;
                }

                $exportedPage = new Page();
                $exportedPage->uid = $page->getUid();
                $exportedPage->layout = $this->compileLayout($page->getLayout(), $formFieldRecords);
                $exportedPage->label = $page->getLabel();

                $exported->pages->add($exportedPage);
            }

            $collection->add($exported);
        }

        return $collection;
    }

    protected function collectIntegrations(?array $ids = null): IntegrationCollection
    {
        $securityKey = \Craft::$app->getConfig()->getGeneral()->securityKey;
        $collection = new IntegrationCollection();

        $integrations = Freeform::getInstance()->integrations->getAllIntegrations();
        foreach ($integrations as $integration) {
            if (null !== $ids && !\in_array($integration->uid, $ids, true)) {
                continue;
            }

            $exported = new Integration();
            $exported->name = $integration->name;
            $exported->handle = $integration->handle;
            $exported->uid = $integration->uid;
            $exported->type = $integration->type;
            $exported->class = $integration->class;

            $metadata = $integration->metadata;

            $properties = $this->propertyProvider->getEditableProperties($integration->class);
            foreach ($properties as $property) {
                if (!$property->hasFlag(IntegrationInterface::FLAG_ENCRYPTED)) {
                    continue;
                }

                $value = $metadata[$property->handle] ?? null;
                $isEnvVariable = StringHelper::isEnvVariable($value);
                if (!$isEnvVariable && $value) {
                    $value = \Craft::$app->security->decryptByKey(base64_decode($value), $securityKey);
                }

                $metadata[$property->handle] = $value;
            }

            $exported->metadata = $metadata;

            $collection->add($exported);
        }

        return $collection;
    }

    protected function collectNotifications(?array $ids = null): NotificationTemplateCollection
    {
        $collection = new NotificationTemplateCollection();
        $notifications = Freeform::getInstance()->notifications->getAllNotifications();

        foreach ($notifications as $notification) {
            $uid = $notification->uid ?? $notification->filepath;
            if (null !== $ids && !\in_array($uid, $ids, true)) {
                continue;
            }

            $exported = new NotificationTemplate();
            $exported->uid = $uid;
            $exported->id = $notification->id;
            $exported->isFile = (bool) $notification->filepath;

            $exported->name = $notification->name;
            $exported->handle = $notification->handle;
            $exported->description = $notification->description;

            $exported->fromName = $notification->fromName ?? '{{ craft.app.projectConfig.get("email.fromName") }}';
            $exported->fromEmail = $notification->fromEmail ?? '{{ craft.app.projectConfig.get("email.fromEmail") }}';
            $exported->replyToName = $notification->replyToName ?? null;
            $exported->replyToEmail = $notification->replyToEmail ?? null;
            $exported->cc = FreeformStringHelper::extractSeparatedValues($notification->cc ?? '');
            $exported->bcc = FreeformStringHelper::extractSeparatedValues($notification->bcc ?? '');

            $exported->includeAttachments = $notification->isIncludeAttachmentsEnabled();

            $exported->subject = $notification->subject ?? '';
            $exported->body = $notification->bodyHtml ?? '';
            $exported->textBody = $notification->bodyText ?? '';
            $exported->autoText = $notification->isAutoText();

            $collection->add($exported);
        }

        return $collection;
    }

    protected function collectSubmissions(?array $ids = null): FormSubmissionCollection
    {
        $collection = new FormSubmissionCollection();

        $forms = Freeform::getInstance()->forms->getAllForms();

        foreach ($forms as $form) {
            if (null !== $ids && !\in_array($form->getUid(), $ids, true)) {
                continue;
            }

            $submissions = FFSubmission::find()->formId($form->getId());

            $formSubmissions = new FormSubmissions();
            $formSubmissions->formUid = $form->getUid();
            $formSubmissions->submissionBatchProcessor = new ElementQueryProcessor($submissions);
            $formSubmissions->setProcessor(
                function (FFSubmission $row) use ($form) {
                    $exported = new Submission();
                    $exported->title = $row->title;
                    $exported->status = $row->status;

                    foreach ($form->getLayout()->getFields() as $field) {
                        $exported->{$field->getHandle()} = $row->{$field->getHandle()}->getValue();
                    }

                    return $exported;
                }
            );

            $collection->add($formSubmissions, $form->getUid());
        }

        return $collection;
    }

    protected function collectSettings(bool $collect): ?Settings
    {
        if (!$collect) {
            return null;
        }

        return Freeform::getInstance()->settings->getSettingsModel();
    }

    private function collectRules(FreeformForm $form): RulesCollection
    {
        $collection = new RulesCollection();

        $fieldRules = $this->ruleProvider->getFieldRules($form);
        foreach ($fieldRules as $rule) {
            $collection->add(
                $this->compileRule(
                    $rule,
                    [
                        'fieldUid' => $rule->getFieldUid(),
                        'display' => $rule->getDisplay(),
                    ]
                )
            );
        }

        $pageRules = $this->ruleProvider->getPageRules($form);
        foreach ($pageRules as $rule) {
            $collection->add(
                $this->compileRule(
                    $rule,
                    ['pageUid' => $rule->getPageUid()]
                )
            );
        }

        $buttonRules = $this->ruleProvider->getButtonRules($form);
        foreach ($buttonRules as $rule) {
            $collection->add(
                $this->compileRule(
                    $rule,
                    [
                        'pageUid' => $rule->getPageUid(),
                        'display' => $rule->getDisplay(),
                        'button' => $rule->getButton(),
                    ]
                )
            );
        }

        $rule = $this->ruleProvider->getSubmitFormRule($form);
        if ($rule) {
            $collection->add($this->compileRule($rule));
        }

        $notificationRules = $this->ruleProvider->getNotificationRules($form);
        foreach ($notificationRules as $rule) {
            $collection->add(
                $this->compileRule(
                    $rule,
                    [
                        'notificationUid' => $rule->getNotification()->getUid(),
                        'send' => $rule->isSend(),
                    ]
                )
            );
        }

        return $collection;
    }

    private function compileRule(RuleInterface $rule, array $metadata = []): Rule
    {
        $exported = new Rule();
        $exported->uid = $rule->getUid();
        $exported->type = $rule::class;
        $exported->combinator = $rule->getCombinator();
        $exported->metadata = $metadata;

        foreach ($rule->getConditions() as $condition) {
            $exportedCondition = new RuleCondition();
            $exportedCondition->uid = $condition->getUid();
            $exportedCondition->fieldUid = $condition->getFieldUid();
            $exportedCondition->value = $condition->getValue();
            $exportedCondition->operator = $condition->getOperator();

            $exported->conditions->add($exportedCondition);
        }

        return $exported;
    }

    private function compileLayout(FreeformLayout $layout, array $fieldRecordCache): Layout
    {
        $exportedLayout = new Layout();
        $exportedLayout->uid = $layout->getUid();
        $exportedLayout->rows = new RowCollection();

        foreach ($layout->getRows() as $row) {
            $exportedRow = new Row();
            $exportedRow->uid = $row->getUid();
            $exportedRow->fields = new FieldCollection();

            foreach ($row->getFields() as $field) {
                $fieldRecord = $fieldRecordCache[$field->getUid()] ?? null;
                if (null === $fieldRecord) {
                    continue;
                }

                $exportedField = new Field();
                $exportedField->uid = $field->getUid();
                $exportedField->name = $field->getLabel();
                $exportedField->handle = $field->getHandle();
                $exportedField->type = $field::class;
                $exportedField->required = $field->isRequired();
                $exportedField->metadata = json_decode($fieldRecord->metadata, true);

                if ($field instanceof GroupField) {
                    $exportedField->layout = $this->compileLayout($field->getLayout(), $fieldRecordCache);
                }

                $exportedRow->fields->add($exportedField);
            }

            $exportedLayout->rows->add($exportedRow);
        }

        return $exportedLayout;
    }
}
