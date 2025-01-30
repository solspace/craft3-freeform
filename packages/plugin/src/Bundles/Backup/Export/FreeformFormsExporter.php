<?php

namespace Solspace\Freeform\Bundles\Backup\Export;

use craft\db\Query;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Backup\BatchProcessing\ElementQueryProcessor;
use Solspace\Freeform\Bundles\Backup\Collections\FieldCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormSubmissionCollection;
use Solspace\Freeform\Bundles\Backup\Collections\IntegrationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\NotificationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\PageCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RowCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RulesCollection;
use Solspace\Freeform\Bundles\Backup\Collections\SitesCollection;
use Solspace\Freeform\Bundles\Backup\Collections\TemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\FileTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\NotificationTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\PdfTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\TranslationCollection;
use Solspace\Freeform\Bundles\Backup\DTO\Field;
use Solspace\Freeform\Bundles\Backup\DTO\Form;
use Solspace\Freeform\Bundles\Backup\DTO\FormIntegration;
use Solspace\Freeform\Bundles\Backup\DTO\FormSubmissions;
use Solspace\Freeform\Bundles\Backup\DTO\ImportPreview;
use Solspace\Freeform\Bundles\Backup\DTO\Integration;
use Solspace\Freeform\Bundles\Backup\DTO\Layout;
use Solspace\Freeform\Bundles\Backup\DTO\Notification;
use Solspace\Freeform\Bundles\Backup\DTO\Page;
use Solspace\Freeform\Bundles\Backup\DTO\Row;
use Solspace\Freeform\Bundles\Backup\DTO\Rule;
use Solspace\Freeform\Bundles\Backup\DTO\RuleCondition;
use Solspace\Freeform\Bundles\Backup\DTO\Site;
use Solspace\Freeform\Bundles\Backup\DTO\Submission;
use Solspace\Freeform\Bundles\Backup\DTO\Templates\FileTemplate;
use Solspace\Freeform\Bundles\Backup\DTO\Templates\NotificationTemplate;
use Solspace\Freeform\Bundles\Backup\DTO\Templates\PdfTemplate;
use Solspace\Freeform\Bundles\Backup\DTO\Translation;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\Bundles\Rules\RuleProvider;
use Solspace\Freeform\Elements\Submission as FFSubmission;
use Solspace\Freeform\Fields\Implementations\Pro\GroupField;
use Solspace\Freeform\Form\Form as FreeformForm;
use Solspace\Freeform\Form\Layout\Layout as FreeformLayout;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\FormTemplate;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Library\Helpers\StringHelper as FreeformStringHelper;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Rules\RuleInterface;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\Form\FormFieldRecord;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\Form\FormSiteRecord;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Records\FormTranslationRecord;
use Solspace\Freeform\Records\IntegrationRecord;
use Solspace\Freeform\Records\PdfTemplateRecord;
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
        $preview->settings = (bool) $this->collectSettings(true);
        $preview->templates = (new TemplateCollection())
            ->setPdf($this->collectPdfTemplates())
            ->setNotification($this->collectNotifications())
            ->setFormatting($this->collectFormattingTemplates())
            ->setSuccess($this->collectSuccessTemplates())
        ;

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
            ->groupBy('f.[[uid]]')
            ->orderBy([])
            ->indexBy('f.uid')
            ->column()
        ;

        $formSubmissions = [];
        foreach ($submissions as $uid => $count) {
            $formSubmissions[] = [
                'form' => [
                    'uid' => $uid,
                    'name' => $uidToNameMap[$uid] ?? 'Unknown',
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

            $isSitesEnabled = Freeform::getInstance()->settings->getSettingsModel()->sitesEnabled;
            if ($isSitesEnabled) {
                $exported->sites = new SitesCollection();

                /** @var FormSiteRecord[] $siteRecords */
                $siteRecords = FormSiteRecord::find()->where(['formId' => $form->getId()])->all();
                foreach ($siteRecords as $siteRecord) {
                    $exportedSite = new Site();
                    $exportedSite->id = $siteRecord->getSite()->id;
                    $exportedSite->handle = $siteRecord->getSite()->handle;

                    $exported->sites->add($exportedSite, $exportedSite->id);
                }
            }

            $exported->translations = new TranslationCollection();

            /** @var FormTranslationRecord[] $translationRecords */
            $translationRecords = FormTranslationRecord::find()->where(['formId' => $form->getId()])->all();
            foreach ($translationRecords as $translationRecord) {
                $exportedTranslation = new Translation();
                $exportedTranslation->uid = $translationRecord->uid;
                $exportedTranslation->site = \Craft::$app->sites->getSiteById($translationRecord->siteId)->handle;
                $exportedTranslation->metadata = json_decode($translationRecord->translations, false);

                $exported->translations->add($exportedTranslation);
            }

            $exported->rules = $this->collectRules($form);
            $exported->notifications = new NotificationCollection();

            $notifications = $this->notificationsProvider->getRecordsByForm($form);
            foreach ($notifications as $notification) {
                $metadata = json_decode($notification->metadata, true);

                $exportNotification = new Notification();
                $exportNotification->id = $notification->id;
                $exportNotification->uid = $notification->uid;
                $exportNotification->enabled = $notification->enabled;
                $exportNotification->idAttribute = 'template';
                $exportNotification->name = $metadata['name'] ?? 'Admin Notification';
                $exportNotification->type = $notification->class;
                $exportNotification->metadata = $metadata;

                $exported->notifications->add($exportNotification);
            }

            $integrationUids = (new Query())
                ->from(IntegrationRecord::TABLE)
                ->indexBy('id')
                ->select('uid')
                ->column()
            ;

            /** @var FormIntegrationRecord[] $formIntegrations */
            $formIntegrations = FormIntegrationRecord::find()->where(['formId' => $form->getId()])->all();
            foreach ($formIntegrations as $formIntegration) {
                $integrationUid = $integrationUids[$formIntegration->integrationId] ?? null;
                if (!$integrationUid) {
                    continue;
                }

                $exportIntegration = new FormIntegration();
                $exportIntegration->uid = $formIntegration->uid;
                $exportIntegration->integrationUid = $integrationUid;
                $exportIntegration->metadata = json_decode($formIntegration->metadata, false);
                $exportIntegration->enabled = $formIntegration->enabled;

                $exported->integrations->add($exportIntegration);
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
            $exported->enabled = $integration->enabled;

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
            $exported->pdfTemplateIds = array_map('intval', json_decode($notification->pdfTemplateIds ?: '[]', true));

            $exported->subject = $notification->subject ?? '';
            $exported->body = $notification->bodyHtml ?? '';
            $exported->textBody = $notification->bodyText ?? '';
            $exported->autoText = $notification->isAutoText();

            $collection->add($exported);
        }

        return $collection;
    }

    protected function collectPdfTemplates(?array $ids = null): PdfTemplateCollection
    {
        $collection = new PdfTemplateCollection();
        $templates = PdfTemplateRecord::find()->all();

        /** @var PdfTemplateRecord $template */
        foreach ($templates as $template) {
            $uid = $template->uid;
            if (null !== $ids && !\in_array($uid, $ids, true)) {
                continue;
            }

            $exported = new PdfTemplate();
            $exported->uid = $uid;
            $exported->id = $template->id;

            $exported->name = $template->name;
            $exported->fileName = $template->fileName;
            $exported->description = $template->description;
            $exported->body = $template->body;

            $collection->add($exported);
        }

        return $collection;
    }

    protected function collectFormattingTemplates(?array $ids = null): FileTemplateCollection
    {
        return $this->collectFileTemplates(
            Freeform::getInstance()->settings->getCustomFormTemplates(),
            $ids,
        );
    }

    protected function collectSuccessTemplates(?array $ids = null): FileTemplateCollection
    {
        return $this->collectFileTemplates(
            Freeform::getInstance()->settings->getSuccessTemplates(),
            $ids,
        );
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

        foreach ($layout->getAllRows() as $row) {
            $exportedRow = new Row();
            $exportedRow->uid = $row->getUid();
            $exportedRow->fields = new FieldCollection();

            foreach ($row->getAllFields() as $field) {
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

    private function collectFileTemplates(array $templates, ?array $ids = null): FileTemplateCollection
    {
        $collection = new FileTemplateCollection();

        /** @var FormTemplate $template */
        foreach ($templates as $template) {
            if (null !== $ids && !\in_array($template->getFileName(), $ids, true)) {
                continue;
            }

            $fileTemplate = new FileTemplate();
            $fileTemplate->name = $template->getName();
            $fileTemplate->fileName = $template->getFileName();
            $fileTemplate->path = $template->getFilePath();

            $collection->add($fileTemplate);
        }

        return $collection;
    }
}
