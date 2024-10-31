<?php

namespace Solspace\Freeform\Bundles\Backup\Export;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Backup\BatchProcessing\FileLineProcessor;
use Solspace\Freeform\Bundles\Backup\Collections\FieldCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormCollection;
use Solspace\Freeform\Bundles\Backup\Collections\FormSubmissionCollection;
use Solspace\Freeform\Bundles\Backup\Collections\IntegrationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RowCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RuleConditionCollection;
use Solspace\Freeform\Bundles\Backup\Collections\SitesCollection;
use Solspace\Freeform\Bundles\Backup\Collections\TemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\FileTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\NotificationTemplateCollection;
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
use Solspace\Freeform\Bundles\Backup\DTO\Translation;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationTypeProvider;
use Solspace\Freeform\Form\Settings\Settings as FormSettings;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\Form\Defaults\Defaults;
use Solspace\Freeform\Library\Helpers\FileHelper;
use Solspace\Freeform\Models\Settings;
use yii\web\NotFoundHttpException;

class FileExportReader extends BaseExporter
{
    public function __construct(
        private PropertyProvider $propertyProvider,
        private IntegrationTypeProvider $integrationTypeProvider,
    ) {}

    public function collectDataPreview(): ImportPreview
    {
        $preview = new ImportPreview();

        $preview->forms = $this->collectForms();
        $preview->integrations = $this->collectIntegrations();
        $preview->settings = (bool) $this->collectSettings(true);
        $preview->templates = (new TemplateCollection())
            ->setNotification($this->collectNotifications())
            ->setFormatting($this->collectFormattingTemplates())
            ->setSuccess($this->collectSuccessTemplates())
        ;

        $uidToNameMap = [];
        foreach (Freeform::getInstance()->forms->getAllForms() as $form) {
            $uidToNameMap[$form->getUid()] = $form->getName();
        }

        foreach ($preview->forms as $form) {
            $uidToNameMap[$form->uid] = $form->name;
        }

        $submissions = [];
        foreach ($this->getSubmissionFiles() as $uid => $file) {
            $submissions[] = [
                'form' => [
                    'uid' => $uid,
                    'name' => $uidToNameMap[$uid] ?? Freeform::t("Unknown, won't be imported."),
                ],
                'count' => FileHelper::countLines($file),
            ];
        }

        $preview->formSubmissions = $submissions;

        return $preview;
    }

    protected function collectForms(?array $ids = null): FormCollection
    {
        $collection = new FormCollection();

        foreach ($this->readLineData('forms.jsonl') as $json) {
            if (null !== $ids && !\in_array($json['uid'], $ids)) {
                continue;
            }

            $form = new Form();
            $form->uid = $json['uid'];
            $form->name = $json['name'];
            $form->handle = $json['handle'];
            $form->order = $json['order'];
            $form->settings = new FormSettings($json['settings'], $this->propertyProvider);

            $importedSites = $json['sites'] ?? [];
            if (!empty($importedSites)) {
                $form->sites = new SitesCollection();
                foreach ($importedSites as $siteJson) {
                    $importedSite = new Site();
                    $importedSite->id = $siteJson['id'];
                    $importedSite->handle = $siteJson['handle'];

                    $form->sites->add($importedSite, $importedSite->id);
                }
            }

            foreach ($json['notifications'] as $notificationJson) {
                $notification = new Notification();
                $notification->id = $notificationJson['id'];
                $notification->uid = $notificationJson['uid'];
                $notification->enabled = $notificationJson['enabled'];
                $notification->name = $notificationJson['name'];
                $notification->type = $notificationJson['type'];
                $notification->metadata = $notificationJson['metadata'];
                $notification->idAttribute = 'template';

                $form->notifications->add($notification);
            }

            $translations = $json['translations'] ?? [];
            foreach ($translations as $translationJson) {
                $translation = new Translation();
                $translation->uid = $translationJson['uid'];
                $translation->site = $translationJson['site'];
                $translation->metadata = $translationJson['metadata'];

                $form->translations->add($translation);
            }

            foreach ($json['integrations'] as $formIntegrationJson) {
                $formIntegration = new FormIntegration();
                $formIntegration->uid = $formIntegrationJson['uid'];
                $formIntegration->integrationUid = $formIntegrationJson['integrationUid'];
                $formIntegration->enabled = $formIntegrationJson['enabled'];
                $formIntegration->metadata = (object) $formIntegrationJson['metadata'];

                $form->integrations->add($formIntegration);
            }

            foreach ($json['rules'] as $ruleJson) {
                $rule = new Rule();
                $rule->uid = $ruleJson['uid'];
                $rule->type = $ruleJson['type'];
                $rule->combinator = $ruleJson['combinator'];
                $rule->metadata = $ruleJson['metadata'];
                $rule->conditions = new RuleConditionCollection();

                foreach ($ruleJson['conditions'] as $conditionJson) {
                    $condition = new RuleCondition();
                    $condition->uid = $conditionJson['uid'];
                    $condition->fieldUid = $conditionJson['fieldUid'];
                    $condition->operator = $conditionJson['operator'];
                    $condition->value = $conditionJson['value'];

                    $rule->conditions->add($condition);
                }

                $form->rules->add($rule);
            }

            foreach ($json['pages'] as $pageJson) {
                $page = new Page();
                $page->uid = $pageJson['uid'];
                $page->layout = $this->parseLayout($pageJson['layout']);
                $page->label = $pageJson['label'];

                $form->pages->add($page);
            }

            $collection->add($form);
        }

        return $collection;
    }

    protected function collectIntegrations(?array $ids = null): IntegrationCollection
    {
        $collection = new IntegrationCollection();

        foreach ($this->readLineData('integrations.jsonl') as $json) {
            if (null !== $ids && !\in_array($json['uid'], $ids)) {
                continue;
            }

            $integration = new Integration();
            $integration->uid = $json['uid'];
            $integration->type = $json['type'];
            $integration->class = $json['class'];
            $integration->name = $json['name'];
            $integration->handle = $json['handle'];
            $integration->enabled = $json['enabled'];
            $integration->metadata = $json['metadata'];

            $type = $this->integrationTypeProvider->getTypeDefinition($integration->class);
            $integration->icon = $type->getIconUrl();

            $collection->add($integration);
        }

        return $collection;
    }

    protected function collectNotifications(?array $ids = null): NotificationTemplateCollection
    {
        $collection = new NotificationTemplateCollection();

        foreach ($this->readLineData('notifications.jsonl') as $json) {
            if (null !== $ids && !\in_array($json['uid'], $ids)) {
                continue;
            }

            $template = new NotificationTemplate();
            $template->uid = $json['uid'];
            $template->id = $json['id'];
            $template->isFile = $json['isFile'];

            $template->name = $json['name'];
            $template->handle = $json['handle'];
            $template->description = $json['description'];

            $template->fromName = $json['fromName'] ?? '{{ craft.app.projectConfig.get("email.fromName") }}';
            $template->fromEmail = $json['fromEmail'] ?? '{{ craft.app.projectConfig.get("email.fromEmail") }}';
            $template->replyToName = $json['replyToName'] ?? null;
            $template->replyToEmail = $json['replyToEmail'] ?? null;
            $template->cc = $json['cc'] ?? [];
            $template->bcc = $json['bcc'] ?? [];

            $template->includeAttachments = $json['includeAttachments'];

            $template->subject = $json['subject'] ?? '';
            $template->body = $json['body'] ?? '';
            $template->textBody = $json['textBody'] ?? '';
            $template->autoText = $json['autoText'] ?? false;

            $collection->add($template);
        }

        return $collection;
    }

    protected function collectFormattingTemplates(?array $ids = null): FileTemplateCollection
    {
        return $this->collectFileTemplates('formatting', $ids);
    }

    protected function collectSuccessTemplates(?array $ids = null): FileTemplateCollection
    {
        return $this->collectFileTemplates('success', $ids);
    }

    protected function collectSubmissions(?array $ids = null): FormSubmissionCollection
    {
        $collection = new FormSubmissionCollection();

        $forms = Freeform::getInstance()->forms->getAllForms();
        $formsByUid = [];
        foreach ($forms as $form) {
            $formsByUid[$form->getUid()] = $form->getName();
        }

        $forms = $this->collectForms();
        foreach ($forms as $form) {
            $formsByUid[$form->uid] = $form->name;
        }

        foreach ($this->getSubmissionFiles() as $uid => $file) {
            if (null !== $ids && !\in_array($uid, $ids)) {
                continue;
            }

            $form = $formsByUid[$uid] ?? null;
            if (!$form) {
                continue;
            }

            $formSubmissions = new FormSubmissions();
            $formSubmissions->formUid = $uid;
            $formSubmissions->submissionBatchProcessor = new FileLineProcessor($file);
            $formSubmissions->setProcessor(
                function (array $json) {
                    $exported = new Submission();
                    $exported->title = $json['title'];
                    $exported->status = $json['status'];

                    foreach ($json['values'] as $key => $value) {
                        $exported->{$key} = $value;
                    }

                    return $exported;
                }
            );

            $collection->add($formSubmissions, $uid);
        }

        return $collection;
    }

    protected function collectSettings(bool $collect): ?Settings
    {
        if (!$collect) {
            return null;
        }

        try {
            $content = $this->getFileContents('settings.json');
        } catch (\Exception) {
            return null;
        }

        $json = json_decode($content, true);

        $defaults = $json['defaults'];
        unset($json['defaults']);

        $settings = new Settings($json);
        $settings->defaults = new Defaults($defaults);

        return $settings;
    }

    /**
     * @return false|resource
     *
     * @throws \Exception
     */
    private function getFile(string $name)
    {
        $path = $this->getPath();

        $filePath = $path.'/'.$name;
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        return fopen($filePath, 'r');
    }

    private function getFileContents(string $name): string
    {
        $path = $this->getPath();

        $filePath = $path.'/'.$name;
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        return file_get_contents($filePath);
    }

    private function getPath(): string
    {
        $token = $this->getOption('fileToken');
        if (!$token) {
            throw new \Exception('Token is not defined');
        }

        return \Craft::$app->path->getTempPath().'/freeform-import-'.$token;
    }

    private function readLineData(string $file): \Generator
    {
        try {
            $handle = $this->getFile($file);
        } catch (\Exception) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            yield json_decode($line, true);
        }

        fclose($handle);
    }

    private function getSubmissionFiles(): \Generator
    {
        $path = $this->getPath();

        try {
            $files = scandir($path);
        } catch (\Exception) {
            throw new NotFoundHttpException('Import File no longer exists.');
        }

        foreach ($files as $file) {
            if (!str_starts_with($file, 'submissions-')) {
                continue;
            }

            $uid = $file;
            $uid = str_replace('submissions-', '', $uid);
            $uid = str_replace('.jsonl', '', $uid);

            yield $uid => $path.'/'.$file;
        }
    }

    private function parseLayout(array $layoutJson): Layout
    {
        $layout = new Layout();
        $layout->uid = $layoutJson['uid'];
        $layout->rows = new RowCollection();

        foreach ($layoutJson['rows'] as $rowJson) {
            $row = new Row();
            $row->uid = $rowJson['uid'];
            $row->fields = new FieldCollection();

            foreach ($rowJson['fields'] as $fieldJson) {
                $field = new Field();
                $field->uid = $fieldJson['uid'];
                $field->name = $fieldJson['name'];
                $field->handle = $fieldJson['handle'];
                $field->type = $fieldJson['type'];
                $field->required = $fieldJson['required'];
                $field->metadata = $fieldJson['metadata'];

                $subLayout = $fieldJson['layout'] ?? null;
                if ($subLayout) {
                    $field->layout = $this->parseLayout($subLayout);
                }

                $row->fields->add($field);
            }

            $layout->rows->add($row);
        }

        return $layout;
    }

    private function collectFileTemplates(string $type, ?array $ids = null): FileTemplateCollection
    {
        $collection = new FileTemplateCollection();

        $root = $this->getPath()."/templates/{$type}/";

        foreach ($this->readLineData("{$type}-templates.jsonl") as $json) {
            if (null !== $ids && !\in_array($json['fileName'], $ids)) {
                continue;
            }

            $fileTemplate = new FileTemplate();
            $fileTemplate->name = $json['name'];
            $fileTemplate->fileName = $json['fileName'];
            $fileTemplate->path = $root.$json['fileName'];

            $collection->add($fileTemplate);
        }

        return $collection;
    }
}
