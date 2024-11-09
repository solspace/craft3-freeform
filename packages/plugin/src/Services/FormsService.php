<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Services;

use craft\base\Event;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\App;
use craft\helpers\Template;
use craft\web\View;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Events\Forms\DeleteEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Forms\ReturnUrlEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Form\Settings\Settings as FormSettings;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Database\FormHandlerInterface;
use Solspace\Freeform\Library\Exceptions\FormExceptions\InvalidFormTypeException;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\Form\FormSiteRecord;
use Solspace\Freeform\Records\FormRecord;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class FormsService extends BaseService implements FormHandlerInterface
{
    private const FALLBACK_TEMPLATE = 'flexbox/index.twig';

    /** @var Form[] */
    private static array $formsById = [];

    /** @var Form[] */
    private static array $formsByHandle = [];

    private static array $allFormsCache = [];

    private static array $spamCountIncrementedForms = [];

    public function __construct(
        ?array $config,
        private PropertyProvider $propertyProvider,
        private TranslationProvider $translationProvider,
    ) {
        parent::__construct($config);
    }

    /**
     * @return Form[]
     */
    public function getAllForms(bool $orderByName = false, null|array|string $sites = null): array
    {
        if ($sites && \is_array($sites)) {
            sort($sites);
        }

        $key = null !== $sites ? md5(json_encode($sites)) : 'all';
        if (!\array_key_exists($key, self::$allFormsCache)) {
            $query = $this->getFormQuery();
            $this->attachSitesToQuery($query, $sites);

            if ($orderByName) {
                $query->orderBy(['forms.name' => \SORT_ASC]);
            } else {
                $query->orderBy(['forms.order' => \SORT_ASC]);
            }

            $results = $query->all();

            self::$allFormsCache[$key] = [];
            foreach ($results as $result) {
                try {
                    $form = $this->createForm($result);

                    self::$allFormsCache[$key][$form->getId()] = $form;
                    self::$formsById[$form->getId()] = $form;
                    self::$formsByHandle[$form->getHandle()] = $form;
                } catch (InvalidFormTypeException) {
                }
            }
        }

        return self::$allFormsCache[$key];
    }

    public function getAllNonArchivedForms(bool $orderByName = false, null|array|string $sites = null): array
    {
        if ($sites && \is_array($sites)) {
            sort($sites);
        }

        $key = null !== $sites ? md5(json_encode($sites)) : 'all';
        if (!\array_key_exists($key, self::$allFormsCache)) {
            $query = $this->getFormQuery();
            $this->attachSitesToQuery($query, $sites);

            $query->where(['forms.dateArchived' => null]);

            if ($orderByName) {
                $query->orderBy(['forms.name' => \SORT_ASC]);
            } else {
                $query->orderBy(['forms.order' => \SORT_ASC]);
            }

            $results = $query->all();

            self::$allFormsCache[$key] = [];
            foreach ($results as $result) {
                try {
                    $form = $this->createForm($result);

                    self::$allFormsCache[$key][$form->getId()] = $form;
                    self::$formsById[$form->getId()] = $form;
                    self::$formsByHandle[$form->getHandle()] = $form;
                } catch (InvalidFormTypeException) {
                }
            }
        }

        return self::$allFormsCache[$key];
    }

    public function getResolvedForms(array $arguments = []): array
    {
        $limit = $arguments['limit'] ?? null;
        $sort = strtolower($arguments['sort'] ?? 'asc');
        $sort = 'desc' === $sort ? \SORT_DESC : \SORT_ASC;

        $orderBy = $arguments['orderBy'] ?? 'order';
        $orderBy = [$orderBy => $sort];

        $offset = $arguments['offset'] ?? null;

        unset($arguments['limit'], $arguments['orderBy'], $arguments['sort'], $arguments['offset']);

        $query = $this
            ->getFormQuery()
            ->where($arguments)
            ->orderBy($orderBy)
            ->limit($limit)
            ->offset($offset)
        ;

        $results = $query->all();

        $forms = [];
        foreach ($results as $result) {
            try {
                $forms[] = $this->createForm($result);
            } catch (InvalidFormTypeException) {
            }
        }

        return $forms;
    }

    public function getAllFormIds(?string $type = null): array
    {
        $query = $this->getFormQuery()->select('id');
        if (null !== $type) {
            $query->where(['type' => $type]);
        }

        return $query->column();
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

    public function getFormById(int $id, bool $refresh = false, ?string $site = null): ?Form
    {
        if (!$refresh && (null === self::$formsById || !isset(self::$formsById[$id]))) {
            $query = $this->getFormQuery();
            $this->attachSitesToQuery($query, $site);
            $query->where(['forms.id' => $id]);

            $result = $query->one();
            if (!$result) {
                self::$formsById[$id] = null;

                return null;
            }

            try {
                $form = $this->createForm($result);
            } catch (InvalidFormTypeException) {
                $form = null;
            }

            self::$formsByHandle[$form->getHandle()] = $form;
            self::$formsById[$id] = $form;
        }

        return self::$formsById[$id];
    }

    public function getFormByHandle(string $handle, ?string $site = null): ?Form
    {
        if (null === self::$formsByHandle || !isset(self::$formsByHandle[$handle])) {
            $query = $this->getFormQuery();
            $this->attachSitesToQuery($query, $site);
            $query->andWhere(['forms.handle' => $handle]);

            $result = $query->one();
            if (!$result) {
                self::$formsByHandle[$handle] = null;

                return null;
            }

            try {
                $form = $this->createForm($result);
            } catch (InvalidFormTypeException) {
                $form = null;
            }

            self::$formsById[$form->getId()] = $form;
            self::$formsByHandle[$handle] = $form;
        }

        return self::$formsByHandle[$handle];
    }

    public function getFormByHandleOrId(int|string $handleOrId, ?string $site = null): ?Form
    {
        if (is_numeric($handleOrId)) {
            return $this->getFormById($handleOrId, site: $site);
        }

        return $this->getFormByHandle($handleOrId, $site);
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
            ->execute()
        ;

        self::$spamCountIncrementedForms[$handle] = $spamBlockCount;

        return $spamBlockCount;
    }

    public function deleteById(int $formId): bool
    {
        App::maxPowerCaptain();
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
                $ids = array_map(
                    fn (Submission $submission) => $submission->getId(),
                    $submissions
                );

                \Craft::$app
                    ->db
                    ->createCommand()
                    ->delete(Table::ELEMENTS, ['id' => $ids])
                    ->execute()
                ;
            }

            $affectedRows = \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(FormRecord::TABLE, ['id' => $formId])
                ->execute()
            ;

            $transaction?->commit();

            \Craft::$app
                ->getDb()
                ->createCommand()
                ->dropTableIfExists(Submission::generateContentTableName($formId, $record->getHandle()))
                ->execute()
            ;

            $this->trigger(self::EVENT_AFTER_DELETE, new DeleteEvent($record));

            return (bool) $affectedRows;
        } catch (\Exception $exception) {
            if (null !== $transaction) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    public function renderFormTemplate(Form $form, string $templateName): ?Markup
    {
        $settings = $this->getSettingsService();

        if (empty($templateName)) {
            return null;
        }

        $customTemplates = $settings->getCustomFormTemplates();
        $solspaceTemplates = $settings->getSolspaceFormTemplates();

        $fallback = null;
        $templateMode = View::TEMPLATE_MODE_SITE;
        $templatePath = null;
        foreach ($customTemplates as $template) {
            if (str_ends_with($template->getFilePath(), $templateName)) {
                $templatePath = $template->getFilePath();

                break;
            }
        }

        if (!$templatePath) {
            foreach ($solspaceTemplates as $template) {
                if (self::FALLBACK_TEMPLATE === $template->getFileName()) {
                    $fallback = $template;
                }

                if (str_ends_with($template->getFilePath(), $templateName)) {
                    $templatePath = $template->getFilePath();
                    $templateMode = View::TEMPLATE_MODE_CP;

                    break;
                }
            }
        }

        if (null === $templatePath || !file_exists($templatePath)) {
            $templatePath = $fallback?->getFilePath();
            $templateMode = View::TEMPLATE_MODE_CP;

            Freeform::getInstance()
                ->logger
                ->getLogger('templates')
                ->warning(
                    \sprintf(
                        "Form template '%s' not found for form #%d '%s'. Using fallback template '%s'",
                        $templateName,
                        $form->getId(),
                        $form->getName(),
                        $fallback?->getFileName() ?? self::FALLBACK_TEMPLATE,
                    )
                )
            ;
        }

        $output = \Craft::$app->view->renderString(
            file_get_contents($templatePath),
            ['form' => $form],
            $templateMode,
        );

        return Template::raw($output);
    }

    public function renderSuccessTemplate(Form $form): ?Markup
    {
        $settings = $this->getSettingsService();
        $templateName = $form->getSettings()->getBehavior()->successTemplate;
        if (empty($templateName)) {
            return null;
        }

        $templates = $settings->getSuccessTemplates();

        $templatePath = null;
        foreach ($templates as $template) {
            if ($template->getFileName() === $templateName) {
                $templatePath = $template->getFilePath();
                //  $templatePath = str_replace(\Craft::getAlias('@templates'), '', $templatePath);

                break;
            }
        }

        //  if (null === $templatePath) {
        if (null === $templatePath || !file_exists($templatePath)) {
            throw new FreeformException(
                Freeform::t(
                    "Success template '{name}' not found",
                    ['name' => $templateName]
                )
            );
        }

        //  $output = \Craft::$app->view->renderTemplate(
        //      $templatePath,
        $output = \Craft::$app->view->renderString(
            file_get_contents($templatePath),
            ['form' => $form]
        );

        return Template::raw($output);
    }

    public function isSpamBehaviorSimulateSuccess(): bool
    {
        return $this->getSettingsService()->isSpamBehaviorSimulatesSuccess();
    }

    public function isSpamBehaviorReloadForm(): bool
    {
        return $this->getSettingsService()->isSpamBehaviorReloadForm();
    }

    public function isSpamFolderEnabled(): bool
    {
        return $this->getSettingsService()->isSpamFolderEnabled();
    }

    public function isAjaxEnabledByDefault(): bool
    {
        return $this->getSettingsService()->isAjaxEnabledByDefault();
    }

    public function addFormPluginScripts(RenderTagEvent $event): void
    {
        if ($event->isScriptsDisabled()) {
            return;
        }

        $event->addScript($this->getSettingsService()->getPluginJsPath());
        $event->addStylesheet($this->getSettingsService()->getPluginCssPath());
    }

    public function collectScripts(CollectScriptsEvent $event): void
    {
        $event->addScript('freeform', $this->getSettingsService()->getPluginJsPath());
        $event->addStylesheet('freeform', $this->getSettingsService()->getPluginCssPath());
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
        if ($this->getSettingsService()->getSettingsModel()->defaults->includeSampleTemplates) {
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

    public function getReturnUrl(Form $form): ?string
    {
        $submission = $form->getSubmission();

        try {
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

            $event = new ReturnUrlEvent($form, $submission, $returnUrl);
            Event::trigger(Form::class, Form::EVENT_GENERATE_RETURN_URL, $event);
            $returnUrl = $event->getReturnUrl();

            if (!$returnUrl) {
                $returnUrl = $request->getUrl();
            }

            return $returnUrl;
        } catch (Exception|InvalidConfigException|LoaderError|SyntaxError) {
        }

        return null;
    }

    public function getFormsFromQuery(Query $query): array
    {
        $baseQuery = $this->getFormQuery();
        $query
            ->select($baseQuery->select)
            ->from($baseQuery->from)
        ;

        $results = $query->all();

        $forms = [];
        foreach ($results as $result) {
            try {
                $form = $this->createForm($result);

                $forms[] = $form;
                self::$formsById[$form->getId()] = $form;
                self::$formsByHandle[$form->getHandle()] = $form;
            } catch (InvalidFormTypeException) {
            }
        }

        return $forms;
    }

    public function getFormQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'forms.uid',
                    'forms.id',
                    'forms.type',
                    'forms.name',
                    'forms.handle',
                    'forms.metadata',
                    'forms.spamBlockCount',
                    'forms.createdByUserId',
                    'forms.dateCreated',
                    'forms.updatedByUserId',
                    'forms.dateUpdated',
                    'forms.dateArchived',
                ]
            )
            ->from(FormRecord::TABLE.' forms')
            ->orderBy(['forms.order' => \SORT_ASC, 'forms.name' => \SORT_ASC])
        ;
    }

    private function attachSitesToQuery(Query $query, null|array|string $sites = null): void
    {
        if (null === $sites) {
            return;
        }

        if (\is_string($sites)) {
            $sites = [$sites];
        }

        $sites = array_filter($sites);

        $query
            ->innerJoin(FormSiteRecord::TABLE.' fs', 'fs.[[formId]] = forms.[[id]]')
            ->innerJoin(Table::SITES.' sites', 'sites.[[id]] = fs.[[siteId]]')
            ->andWhere(['in', 'sites.[[handle]]', $sites])
        ;
    }

    private function createForm(array $data): Form
    {
        $data['metadata'] = JsonHelper::decode($data['metadata'] ?: '{}', true);

        $type = $data['type'] ?? null;

        try {
            $reflection = new \ReflectionClass($type);
        } catch (\ReflectionException) {
            throw new InvalidFormTypeException(
                \sprintf('Unregistered form type used: "%s"', $type)
            );
        }

        if (!$reflection->isSubclassOf(Form::class)) {
            throw new InvalidFormTypeException(
                \sprintf('Unregistered form type used: "%s"', $type)
            );
        }

        $settings = new FormSettings($data['metadata'], $this->propertyProvider);

        return new $type(
            $data,
            $settings,
            new PropertyAccessor(),
            $this->translationProvider,
        );
    }

    private function addFormManagePermissionToUser($formId): void
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
