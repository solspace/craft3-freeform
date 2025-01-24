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

namespace Solspace\Freeform\Services;

use craft\db\Query;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\AfterSubmitEvent;
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\BeforeSubmitEvent;
use Solspace\Freeform\Events\Forms\DeleteEvent;
use Solspace\Freeform\Events\Forms\FormRenderEvent;
use Solspace\Freeform\Events\Forms\FormValidateEvent;
use Solspace\Freeform\Events\Forms\PageJumpEvent;
use Solspace\Freeform\Events\Forms\ReturnUrlEvent;
use Solspace\Freeform\Events\Forms\SaveEvent;
use Solspace\Freeform\Fields\Pro\FileDragAndDropField;
use Solspace\Freeform\Fields\Pro\OpinionScaleField;
use Solspace\Freeform\Form\Managers\ContentManager;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Database\FormHandlerInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Models\FormModel;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\FormRecord;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\ViewNotFoundException;

class FormsService extends BaseService implements FormHandlerInterface
{
    /** @var FormModel[] */
    private static $formsById = [];

    /** @var FormModel[] */
    private static $formsByHandle = [];

    /** @var bool */
    private static $allFormsLoaded;

    /** @var array */
    private static $spamCountIncrementedForms = [];

    /**
     * @param bool $orderByName
     *
     * @return FormModel[]
     */
    public function getAllForms($orderByName = false): array
    {
        if (null === self::$formsById || !self::$allFormsLoaded) {
            $query = $this->getFormQuery();
            if ($orderByName) {
                $query->orderBy(['forms.name' => \SORT_ASC]);
            }

            $results = $query->all();

            self::$formsById = [];
            foreach ($results as $result) {
                $form = $this->createForm($result);
                if (!$form) {
                    continue;
                }

                self::$formsById[$form->id] = $form;
                self::$formsByHandle[$form->handle] = $form;
            }

            self::$allFormsLoaded = true;
        }

        return self::$formsById;
    }

    /**
     * @return Form[]
     */
    public function getResolvedForms(array $arguments = []): array
    {
        $limit = $arguments['limit'] ?? null;
        $sort = strtolower($arguments['sort'] ?? 'asc');
        $sort = 'desc' === $sort ? \SORT_DESC : \SORT_ASC;

        $orderBy = $arguments['orderBy'] ?? 'order';
        $orderBy = [$orderBy => $sort];

        $offset = $arguments['offset'] ?? null;

        unset($arguments['limit'], $arguments['orderBy'], $arguments['sort'], $arguments['offset']);

        $query = $this->getFormQuery()
            ->where($arguments)
            ->orderBy($orderBy)
            ->limit($limit)
            ->offset($offset)
        ;

        $results = $query->all();

        $forms = [];
        foreach ($results as $result) {
            $model = $this->createForm($result);
            $forms[] = $model->getForm();
        }

        return $forms;
    }

    public function getAllFormIds(): array
    {
        return $this->getFormQuery()
            ->select('id')
            ->column()
        ;
    }

    public function getAllFormNames(bool $indexById = true): array
    {
        $query = $this->getFormQuery();
        $query->select(['forms.id', 'forms.name']);
        $forms = $query->pairs();

        if ($indexById) {
            return $forms;
        }

        return array_values($forms);
    }

    public function getAllowedFormIds(): array
    {
        if (PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_MANAGE)) {
            return $this->getAllFormIds();
        }

        return PermissionHelper::getNestedPermissionIds(Freeform::PERMISSION_FORMS_MANAGE);
    }

    public function getFormById(int $id, bool $refresh = false): ?FormModel
    {
        if (!$refresh && (null === self::$formsById || !isset(self::$formsById[$id]))) {
            $result = $this->getFormQuery()->where(['id' => $id])->one();

            $form = null;
            if ($result) {
                $form = $this->createForm($result);
            }

            if ($form) {
                self::$formsByHandle[$form->handle] = $form;
                self::$formsById[$id] = $form;
            } else {
                return $form;
            }
        }

        return self::$formsById[$id];
    }

    public function getFormByHandle(string $handle): ?FormModel
    {
        if (null === self::$formsByHandle || !isset(self::$formsByHandle[$handle])) {
            $result = $this->getFormQuery()->where(['handle' => $handle])->one();

            $form = null;
            if ($result) {
                $form = $this->createForm($result);
            }

            if ($form) {
                self::$formsById[$form->id] = $form;
                self::$formsByHandle[$handle] = $form;
            } else {
                return null;
            }
        }

        return self::$formsByHandle[$handle];
    }

    /**
     * @param mixed $handleOrId
     *
     * @return null|FormModel
     */
    public function getFormByHandleOrId($handleOrId)
    {
        if (is_numeric($handleOrId)) {
            return $this->getFormById($handleOrId);
        }

        return $this->getFormByHandle($handleOrId);
    }

    /**
     * @throws \Exception
     */
    public function save(FormModel $model): bool
    {
        $isNew = !$model->id;

        if (!$isNew) {
            $record = FormRecord::findOne(['id' => $model->id]);
        } else {
            $record = FormRecord::create();
        }

        $oldLayout = $record->layoutJson;
        $newLayout = $model->layoutJson;

        $record->type = $model->type;
        $record->metadata = json_encode($model->metadata);
        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->spamBlockCount = $model->spamBlockCount;
        $record->submissionTitleFormat = $model->submissionTitleFormat;
        $record->description = $model->description;
        $record->layoutJson = $model->layoutJson;
        $record->returnUrl = $model->returnUrl;
        $record->extraPostUrl = $model->extraPostUrl;
        $record->extraPostTriggerPhrase = $model->extraPostTriggerPhrase;
        $record->defaultStatus = $model->defaultStatus;
        $record->formTemplateId = $model->formTemplateId;
        $record->color = $model->color;
        $record->optInDataStorageTargetHash = $model->optInDataStorageTargetHash;
        $record->limitFormSubmissions = $model->limitFormSubmissions;
        $record->gtmEnabled = $model->gtmEnabled;
        $record->gtmId = $model->gtmId;
        $record->gtmEventName = $model->gtmEventName;

        if ($isNew) {
            $record->order = 1 + ((int) (new Query())
                ->select('MAX([[order]])')
                ->from(FormRecord::TABLE)
                ->scalar());
        }

        $record->validate();
        $model->addErrors($record->getErrors());

        $beforeSaveEvent = new SaveEvent($model, $isNew);
        $this->trigger(self::EVENT_BEFORE_SAVE, $beforeSaveEvent);

        if ($beforeSaveEvent->isValid && !$model->hasErrors()) {
            $transaction = null;
            if (!\Craft::$app->getDb()->getTransaction()) {
                // we start new transaction only in case there is none, otherwise we are not ones responsible for commit
                // TODO: do save for other similar services
                $transaction = \Craft::$app->getDb()->getTransaction() ?? \Craft::$app->getDb()->beginTransaction();
            }

            try {
                $record->save(false);

                if ($isNew) {
                    $model->id = $record->id;
                    $model->uid = $record->uid;
                }

                self::$formsById[$model->id] = $model;

                if (null !== $transaction) {
                    $transaction->commit();
                }

                $this->trigger(self::EVENT_AFTER_SAVE, new SaveEvent($model, $isNew));

                $contentManager = new ContentManager(
                    $model->getForm(true),
                    $oldLayout,
                    $newLayout
                );

                $contentManager->performDatabaseColumnAlterations();
            } catch (\Exception $e) {
                if (null !== $transaction) {
                    $transaction->rollBack();
                }

                throw $e;
            }
        }

        if ($model->hasErrors()) {
            return false;
        }

        if ($isNew) {
            $this->addFormManagePermissionToUser($model->id);
        }

        return true;
    }

    /**
     * Increments the spam block counter by 1.
     *
     * @return int - new spam block count
     */
    public function incrementSpamBlockCount(Form $form): int
    {
        $handle = $form->getHandle();
        if (isset(self::$spamCountIncrementedForms[$handle])) {
            return self::$spamCountIncrementedForms[$handle];
        }

        $spamBlockCount = (int) (new Query())
            ->select(['spamBlockCount'])
            ->from(FormRecord::TABLE)
            ->where(['id' => $form->getId()])
            ->scalar()
        ;

        \Craft::$app
            ->getDb()
            ->createCommand()
            ->update(
                FormRecord::TABLE,
                ['spamBlockCount' => ++$spamBlockCount],
                ['id' => $form->getId()]
            )
            ->execute();

        self::$spamCountIncrementedForms[$handle] = $spamBlockCount;

        return $spamBlockCount;
    }

    /**
     * @param int $formId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteById($formId)
    {
        $record = $this->getFormById($formId);

        if (!$record) {
            return false;
        }

        $beforeDeleteEvent = new DeleteEvent($record);
        $this->trigger(self::EVENT_BEFORE_DELETE, $beforeDeleteEvent);
        if (!$beforeDeleteEvent->isValid) {
            return false;
        }

        $transaction = \Craft::$app->getDb()->getTransaction() ?? \Craft::$app->getDb()->beginTransaction();

        try {
            $submissionQuery = Submission::find()
                ->formId($formId)
                ->skipContent(true)
            ;

            foreach ($submissionQuery->batch() as $submissions) {
                foreach ($submissions as $submission) {
                    \Craft::$app->elements->deleteElement($submission, true);
                }
            }

            $affectedRows = \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(FormRecord::TABLE, ['id' => $formId])
                ->execute();

            if (null !== $transaction) {
                $transaction->commit();
            }

            \Craft::$app
                ->getDb()
                ->createCommand()
                ->dropTableIfExists(Submission::generateContentTableName($formId, $record->handle))
                ->execute();

            $this->trigger(self::EVENT_AFTER_DELETE, new DeleteEvent($record));

            return (bool) $affectedRows;
        } catch (\Exception $exception) {
            if (null !== $transaction) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param string $templateName
     *
     * @throws \InvalidArgumentException
     * @throws ViewNotFoundException
     * @throws InvalidCallException
     * @throws FreeformException
     */
    public function renderFormTemplate(Form $form, $templateName)
    {
        $settings = $this->getSettingsService();

        if (empty($templateName)) {
            return null;
        }

        $customTemplates = $settings->getCustomFormTemplates();
        $solspaceTemplates = $settings->getSolspaceFormTemplates();

        $templatePath = null;
        foreach ($customTemplates as $template) {
            if ($template->getFileName() === $templateName) {
                $templatePath = $template->getFilePath();

                break;
            }
        }

        if (!$templatePath) {
            foreach ($solspaceTemplates as $template) {
                if ($template->getFileName() === $templateName) {
                    $templatePath = $template->getFilePath();

                    break;
                }
            }
        }

        if (null === $templatePath || !file_exists($templatePath)) {
            throw new FreeformException(
                Freeform::t(
                    "Form template '{name}' not found",
                    ['name' => $templateName]
                )
            );
        }

        $output = \Craft::$app->view->renderString(
            file_get_contents($templatePath),
            [
                'form' => $form,
                'formCss' => $this->getFormattingTemplateCss($templateName),
            ]
        );

        return Template::raw($output);
    }

    public function renderSuccessTemplate(Form $form)
    {
        $settings = $this->getSettingsService();
        $templateName = $form->getSuccessTemplate();
        if (empty($templateName)) {
            return null;
        }

        $templates = $settings->getSuccessTemplates();

        $templatePath = null;
        foreach ($templates as $template) {
            if ($template->getFileName() === $templateName) {
                $templatePath = $template->getFilePath();

                break;
            }
        }

        if (null === $templatePath || !file_exists($templatePath)) {
            throw new FreeformException(
                Freeform::t(
                    "Success template '{name}' not found",
                    ['name' => $templateName]
                )
            );
        }

        $output = \Craft::$app->view->renderString(
            file_get_contents($templatePath),
            ['form' => $form]
        );

        return Template::raw($output);
    }

    public function isSpamBehaviourSimulateSuccess(): bool
    {
        return $this->getSettingsService()->isSpamBehaviourSimulatesSuccess();
    }

    public function isSpamBehaviourReloadForm(): bool
    {
        return $this->getSettingsService()->isSpamBehaviourReloadForm();
    }

    public function isSpamFolderEnabled(): bool
    {
        return $this->getSettingsService()->isSpamFolderEnabled();
    }

    public function isAjaxEnabledByDefault(): bool
    {
        return $this->getSettingsService()->isAjaxEnabledByDefault();
    }

    /**
     * @param mixed $deletedStatusId
     * @param mixed $newStatusId
     *
     * @throws \Exception
     */
    public function swapDeletedStatusToDefault($deletedStatusId, $newStatusId)
    {
        $deletedStatusId = (int) $deletedStatusId;
        $newStatusId = (int) $newStatusId;

        $pattern = "/\"defaultStatus\":{$deletedStatusId}(\\}|,)/";

        $forms = $this->getAllForms();
        foreach ($forms as $form) {
            $layout = $form->layoutJson;
            if (preg_match($pattern, $layout)) {
                $layout = preg_replace(
                    $pattern,
                    "\"defaultStatus\":{$newStatusId}$1",
                    $form->layoutJson
                );

                $form->layoutJson = $layout;
                $this->save($form);
            }
        }
    }

    public function addFormPluginScripts(FormRenderEvent $event)
    {
        static $pluginJsLoaded;
        static $pluginCssLoaded;

        $form = $event->getForm();
        $insertType = $this->getSettingsService()->scriptInsertType();

        if ($event->isNoScriptRenderEnabled() && !$event->isManualScriptLoading()) {
            return;
        }

        if (null === $pluginJsLoaded) {
            $jsPath = $this->getSettingsService()->getPluginJsPath();

            switch ($insertType) {
                case Settings::SCRIPT_INSERT_TYPE_INLINE:
                    $js = file_get_contents($jsPath);
                    $event->appendJsToOutput($js);

                    break;

                case Settings::SCRIPT_INSERT_TYPE_FILES:
                    $jsUrl = \Craft::$app->assetManager->getPublishedUrl($jsPath, true);
                    $event->appendExternalJsToOutput($jsUrl);

                    break;

                case Settings::SCRIPT_INSERT_TYPE_POINTERS:
                default:
                    $jsHash = sha1_file($jsPath);
                    $event->appendExternalJsToOutput(UrlHelper::siteUrl('freeform/plugin.js', ['v' => $jsHash]));

                    break;
            }

            $pluginJsLoaded = true;
        }

        $layout = $form->getLayout();
        $isLoadCss = $layout->hasFields(OpinionScaleField::class)
            || $layout->hasFields(FileDragAndDropField::class);

        if (null === $pluginCssLoaded && $isLoadCss) {
            $cssPath = $this->getSettingsService()->getPluginCssPath();

            switch ($insertType) {
                case Settings::SCRIPT_INSERT_TYPE_INLINE:
                    $css = file_get_contents($cssPath);
                    $event->appendCssToOutput($css);

                    break;

                case Settings::SCRIPT_INSERT_TYPE_FILES:
                    $cssUrl = \Craft::$app->assetManager->getPublishedUrl($cssPath, true);
                    $event->appendExternalCssToOutput($cssUrl);

                    break;

                case Settings::SCRIPT_INSERT_TYPE_POINTERS:
                default:
                    $cssHash = sha1_file($cssPath);
                    $event->appendExternalCssToOutput(UrlHelper::siteUrl('freeform/plugin.css', ['v' => $cssHash]));

                    break;
            }

            $pluginCssLoaded = true;
        }
    }

    public function shouldScrollToAnchor(Form $form): bool
    {
        return $this->isAutoscrollToErrorsEnabled() && $form->isFormPosted();
    }

    public function isAutoscrollToErrorsEnabled(): bool
    {
        return $this->getSettingsService()->isAutoScrollToErrors();
    }

    public function isFormSubmitDisable(): bool
    {
        return $this->getSettingsService()->isFormSubmitDisable();
    }

    public function getDefaultFormattingTemplate(): string
    {
        $default = $this->getSettingsService()->getSettingsModel()->formattingTemplate;

        $templateList = [];
        if ($this->getSettingsService()->getSettingsModel()->defaultTemplates) {
            foreach ($this->getSettingsService()->getSolspaceFormTemplates() as $formTemplate) {
                $templateList[] = $formTemplate->getFileName();
            }
        }

        foreach ($this->getSettingsService()->getCustomFormTemplates() as $formTemplate) {
            $templateList[] = $formTemplate->getFileName();
        }

        if (\in_array($default, $templateList, true)) {
            return $default;
        }

        return array_shift($templateList) ?? 'flexbox.html';
    }

    public function getFormattingTemplateCss(string $templateName): string
    {
        $fileName = pathinfo($templateName, \PATHINFO_FILENAME);
        $cssFilePath = \Yii::getAlias('@freeform').'/Resources/css/front-end/formatting-templates/'.$fileName.'.css';
        if (file_exists($cssFilePath)) {
            return file_get_contents($cssFilePath);
        }

        return '';
    }

    public function onBeforeSubmit(Form $form): bool
    {
        $event = new BeforeSubmitEvent($form);
        $this->trigger(self::EVENT_BEFORE_SUBMIT, $event);

        return $event->isValid;
    }

    public function onAfterSubmit(Form $form, Submission $submission = null)
    {
        $event = new AfterSubmitEvent($form, $submission);
        $this->trigger(self::EVENT_AFTER_SUBMIT, $event);
    }

    public function onBeforePageJump(Form $form)
    {
        $event = new PageJumpEvent($form);
        $this->trigger(self::EVENT_PAGE_JUMP, $event);

        return $event->getJumpToIndex();
    }

    public function onRenderOpeningTag(Form $form): string
    {
        $event = new FormRenderEvent($form);
        $this->trigger(self::EVENT_RENDER_OPENING_TAG, $event);

        return $event->getOrAttachOutputToView();
    }

    public function onRenderClosingTag(Form $form): string
    {
        $event = new FormRenderEvent($form);
        $this->trigger(self::EVENT_RENDER_CLOSING_TAG, $event);

        return $event->getOrAttachOutputToView();
    }

    public function onAttachFormAttributes(Form $form, array $attributes = [])
    {
        $event = new AttachFormAttributesEvent($form, $attributes);
        $this->trigger(self::EVENT_ATTACH_FORM_ATTRIBUTES, $event);

        return $event->getAttributes();
    }

    public function onFormValidate(Form $form)
    {
        $event = new FormValidateEvent($form);
        $this->trigger(self::EVENT_FORM_VALIDATE, $event);
    }

    public function onAfterFormValidate(Form $form)
    {
        $event = new FormValidateEvent($form);
        $this->trigger(self::EVENT_AFTER_FORM_VALIDATE, $event);
    }

    public function onAfterGenerateReturnUrl(Form $form, Submission $submission = null, string $returnUrl = null)
    {
        $event = new ReturnUrlEvent($form, $submission, $returnUrl);
        $this->trigger(self::EVENT_AFTER_GENERATE_RETURN_URL, $event);

        return $event->getReturnUrl();
    }

    public function isPossibleLoadingStaticScripts(): bool
    {
        $client = new Client(['verify' => false]);

        try {
            $response = $client->get(UrlHelper::siteUrl('freeform/plugin.js'));
            $body = (string) $response->getBody();

            return preg_match('/freeform\.js/', $body);
        } catch (GuzzleException) {
        }

        return false;
    }

    public function getReturnUrl(Form $form, Submission $submission): string
    {
        $request = \Craft::$app->getRequest();

        $postedReturnUrl = $request->post(Form::RETURN_URI_KEY);
        if ($postedReturnUrl) {
            $returnUrl = \Craft::$app->security->validateData($postedReturnUrl);
            if (false === $returnUrl) {
                $returnUrl = $form->getReturnUrl();
            }
        } else {
            $returnUrl = $form->getReturnUrl();
        }

        $returnUrl = \Craft::$app->view->renderString(
            $returnUrl,
            [
                'form' => $form,
                'submission' => $submission,
            ]
        );

        /** @deprecated  */
        $returnUrl = $this->onAfterGenerateReturnUrl($form, $submission, $returnUrl);

        $event = new ReturnUrlEvent($form, $submission, $returnUrl);
        Event::trigger(Form::class, Form::EVENT_GENERATE_RETURN_URL, $event);
        $returnUrl = $event->getReturnUrl();

        if (!$returnUrl) {
            $returnUrl = $request->getUrl();
        }

        return $returnUrl;
    }

    private function getFormQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'forms.id',
                    'forms.type',
                    'forms.metadata',
                    'forms.uid',
                    'forms.name',
                    'forms.handle',
                    'forms.spamBlockCount',
                    'forms.submissionTitleFormat',
                    'forms.description',
                    'forms.layoutJson',
                    'forms.returnUrl',
                    'forms.defaultStatus',
                    'forms.formTemplateId',
                    'forms.color',
                ]
            )
            ->from(FormRecord::TABLE.' forms')
            ->orderBy(['forms.order' => \SORT_ASC, 'forms.name' => \SORT_ASC])
        ;
    }

    private function createForm(array $data): FormModel
    {
        if (!\is_array($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'] ?: '{}', true);
        }

        return new FormModel($data);
    }

    private function addFormManagePermissionToUser($formId)
    {
        if (\Craft::Pro !== \Craft::$app->getEdition()) {
            return;
        }

        $userId = \Craft::$app->getUser()->id;
        $permissions = \Craft::$app->getUserPermissions()->getPermissionsByUserId($userId);
        $permissions[] = PermissionHelper::prepareNestedPermission(Freeform::PERMISSION_FORMS_MANAGE, $formId);

        \Craft::$app->getUserPermissions()->saveUserPermissions($userId, $permissions);
    }
}
