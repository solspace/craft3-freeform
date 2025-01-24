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

namespace Solspace\Freeform;

use Composer\Autoload\ClassMapGenerator;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Controllers\ApiController;
use Solspace\Freeform\Controllers\BannersController;
use Solspace\Freeform\Controllers\CodepackController;
use Solspace\Freeform\Controllers\CrmController;
use Solspace\Freeform\Controllers\DashboardController;
use Solspace\Freeform\Controllers\DiagnosticsController;
use Solspace\Freeform\Controllers\FeedsController;
use Solspace\Freeform\Controllers\FieldsController;
use Solspace\Freeform\Controllers\FileUploadController;
use Solspace\Freeform\Controllers\FormsController;
use Solspace\Freeform\Controllers\LogsController;
use Solspace\Freeform\Controllers\MailingListsController;
use Solspace\Freeform\Controllers\Migrations\NotificationsController as MigrateNotificationsController;
use Solspace\Freeform\Controllers\notifications\DatabaseController;
use Solspace\Freeform\Controllers\notifications\FilesController;
use Solspace\Freeform\Controllers\notifications\SenderController;
use Solspace\Freeform\Controllers\PaymentGatewaysController;
use Solspace\Freeform\Controllers\Pro\ExportNotificationsController;
use Solspace\Freeform\Controllers\Pro\ExportProfilesController;
use Solspace\Freeform\Controllers\Pro\Payments\PaymentWebhooksController;
use Solspace\Freeform\Controllers\Pro\Payments\SubscriptionsController;
use Solspace\Freeform\Controllers\Pro\QuickExportController;
use Solspace\Freeform\Controllers\Pro\WebhooksController;
use Solspace\Freeform\Controllers\ResourcesController;
use Solspace\Freeform\Controllers\REST\FormsRESTController;
use Solspace\Freeform\Controllers\REST\SettingsController as RESTSettingsController;
use Solspace\Freeform\Controllers\SettingsController;
use Solspace\Freeform\Controllers\SpamSubmissionsController;
use Solspace\Freeform\Controllers\StatusesController;
use Solspace\Freeform\Controllers\SubmissionsController;
use Solspace\Freeform\Controllers\SubmitController;
use Solspace\Freeform\Controllers\WelcomeScreenController;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Assets\RegisterEvent;
use Solspace\Freeform\Events\Freeform\RegisterCpSubnavItemsEvent;
use Solspace\Freeform\Events\Freeform\RegisterSettingsNavigationEvent;
use Solspace\Freeform\FieldTypes\FormFieldType;
use Solspace\Freeform\FieldTypes\SubmissionFieldType;
use Solspace\Freeform\Jobs\PurgeSpamJob;
use Solspace\Freeform\Jobs\PurgeSubmissionsJob;
use Solspace\Freeform\Jobs\PurgeUnfinalizedAssetsJob;
use Solspace\Freeform\Library\Bundles\BundleInterface;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Pro\Payments\ElementHookHandlers\FormHookHandler;
use Solspace\Freeform\Library\Pro\Payments\ElementHookHandlers\SubmissionHookHandler;
use Solspace\Freeform\Models\FieldModel;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\FeedRecord;
use Solspace\Freeform\Records\StatusRecord;
use Solspace\Freeform\Resources\Bundles\BetaBundle;
use Solspace\Freeform\Resources\Bundles\Pro\Payments\PaymentsBundle;
use Solspace\Freeform\Services\ChartsService;
use Solspace\Freeform\Services\ConnectionsService;
use Solspace\Freeform\Services\CrmService;
use Solspace\Freeform\Services\DashboardService;
use Solspace\Freeform\Services\DiagnosticsService;
use Solspace\Freeform\Services\ExportService;
use Solspace\Freeform\Services\FieldsService;
use Solspace\Freeform\Services\FilesService;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\FormTypesService;
use Solspace\Freeform\Services\FreeformFeedService;
use Solspace\Freeform\Services\HoneypotService;
use Solspace\Freeform\Services\IntegrationsQueueService;
use Solspace\Freeform\Services\IntegrationsService;
use Solspace\Freeform\Services\LockService;
use Solspace\Freeform\Services\LoggerService;
use Solspace\Freeform\Services\MailerService;
use Solspace\Freeform\Services\MailingListsService;
use Solspace\Freeform\Services\NotesService;
use Solspace\Freeform\Services\NotificationsService;
use Solspace\Freeform\Services\PaymentGatewaysService;
use Solspace\Freeform\Services\PreflightService;
use Solspace\Freeform\Services\Pro\DigestService;
use Solspace\Freeform\Services\Pro\ExportNotificationsService;
use Solspace\Freeform\Services\Pro\ExportProfilesService;
use Solspace\Freeform\Services\Pro\PayloadForwardingService;
use Solspace\Freeform\Services\Pro\Payments\PaymentNotificationsService;
use Solspace\Freeform\Services\Pro\Payments\PaymentsService;
use Solspace\Freeform\Services\Pro\Payments\StripeService;
use Solspace\Freeform\Services\Pro\Payments\SubscriptionPlansService;
use Solspace\Freeform\Services\Pro\Payments\SubscriptionsService;
use Solspace\Freeform\Services\Pro\RulesService;
use Solspace\Freeform\Services\Pro\WebhooksService;
use Solspace\Freeform\Services\Pro\WidgetsService;
use Solspace\Freeform\Services\RelationsService;
use Solspace\Freeform\Services\SettingsService;
use Solspace\Freeform\Services\SpamSubmissionsService;
use Solspace\Freeform\Services\StatusesService;
use Solspace\Freeform\Services\SubmissionsService;
use Solspace\Freeform\Services\SummaryService;
use Solspace\Freeform\Twig\Filters\FreeformTwigFilters;
use Solspace\Freeform\Variables\FreeformBannersVariable;
use Solspace\Freeform\Variables\FreeformServicesVariable;
use Solspace\Freeform\Variables\FreeformVariable;
use yii\base\Event;
use yii\db\Query;
use yii\web\ForbiddenHttpException;

/**
 * Class Plugin.
 *
 * @property CrmService                  $crm
 * @property FieldsService               $fields
 * @property FilesService                $files
 * @property FormsService                $forms
 * @property MailerService               $mailer
 * @property MailingListsService         $mailingLists
 * @property NotificationsService        $notifications
 * @property SettingsService             $settings
 * @property StatusesService             $statuses
 * @property SubmissionsService          $submissions
 * @property SpamSubmissionsService      $spamSubmissions
 * @property LoggerService               $logger
 * @property HoneypotService             $honeypot
 * @property IntegrationsService         $integrations
 * @property IntegrationsQueueService    $integrationsQueue
 * @property PaymentGatewaysService      $paymentGateways
 * @property ConnectionsService          $connections
 * @property ChartsService               $charts
 * @property WidgetsService              $widgets
 * @property ExportService               $export
 * @property ExportProfilesService       $exportProfiles
 * @property ExportNotificationsService  $exportNotifications
 * @property RulesService                $rules
 * @property PaymentNotificationsService $paymentNotifications
 * @property PaymentsService             $payments
 * @property StripeService               $stripe
 * @property SubscriptionPlansService    $subscriptionPlans
 * @property SubscriptionsService        $subscriptions
 * @property WebhooksService             $webhooks
 * @property RelationsService            $relations
 * @property PayloadForwardingService    $payloadForwarding
 * @property DigestService               $digest
 * @property SummaryService              $summary
 * @property FreeformFeedService         $feed
 * @property LockService                 $lock
 * @property DiagnosticsService          $diagnostics
 * @property PreflightService            $preflight
 * @property FormTypesService            $formTypes
 */
class Freeform extends Plugin
{
    public const TRANSLATION_CATEGORY = 'freeform';

    public const VIEW_DASHBOARD = 'dashboard';
    public const VIEW_FORMS = 'forms';
    public const VIEW_SUBMISSIONS = 'submissions';
    public const VIEW_FIELDS = 'fields';
    public const VIEW_NOTIFICATIONS = 'notifications';
    public const VIEW_SETTINGS = 'settings';
    public const VIEW_RESOURCES = 'resources';
    public const VIEW_EXPORT_PROFILES = 'export-profiles';

    public const FIELD_DISPLAY_ORDER_TYPE = 'type';
    public const FIELD_DISPLAY_ORDER_NAME = 'name';

    public const EDITION_LITE = 'lite';
    public const EDITION_PRO = 'pro';

    public const PERMISSIONS_HELP_LINK = 'https://docs.solspace.com/craft/freeform/v4/configuration/demo-templates/';
    public const PERMISSION_NAMESPACE = 'Freeform';

    public const VERSION_CACHE_KEY = 'freeform_version';
    public const VERSION_CACHE_TIMESTAMP_KEY = 'freeform_version_timestamp';
    public const VERSION_CACHE_TTL = 86400; // 24-hours

    public const PERMISSION_FORMS_ACCESS = 'freeform-formsAccess';
    public const PERMISSION_FORMS_CREATE = 'freeform-formsCreate';
    public const PERMISSION_FORMS_DELETE = 'freeform-formsDelete';
    public const PERMISSION_FORMS_MANAGE = 'freeform-formsManage';
    public const PERMISSION_FORMS_MANAGE_INDIVIDUAL = 'freeform-formsManageIndividual';
    public const PERMISSION_FIELDS_ACCESS = 'freeform-fieldsAccess';
    public const PERMISSION_FIELDS_MANAGE = 'freeform-fieldsManage';
    public const PERMISSION_SETTINGS_ACCESS = 'freeform-settingsAccess';
    public const PERMISSION_SUBMISSIONS_ACCESS = 'freeform-submissionsAccess';
    public const PERMISSION_SUBMISSIONS_READ = 'freeform-submissionsRead';
    public const PERMISSION_SUBMISSIONS_READ_INDIVIDUAL = 'freeform-submissionsReadIndividual';
    public const PERMISSION_SUBMISSIONS_MANAGE = 'freeform-submissionsManage';
    public const PERMISSION_SUBMISSIONS_MANAGE_INDIVIDUAL = 'freeform-submissionsManageIndividual';
    public const PERMISSION_NOTIFICATIONS_ACCESS = 'freeform-notificationsAccess';
    public const PERMISSION_NOTIFICATIONS_MANAGE = 'freeform-notificationsManage';
    public const PERMISSION_DASHBOARD_ACCESS = 'freeform-dashboardAccess';
    public const PERMISSION_ERROR_LOG_ACCESS = 'freeform-errorLogAccess';
    public const PERMISSION_ERROR_LOG_MANAGE = 'freeform-errorLogManage';
    public const PERMISSION_RESOURCES = 'freeform-resources';
    public const PERMISSION_ACCESS_QUICK_EXPORT = 'freeform-access-quick-export';
    public const PERMISSION_EXPORT_PROFILES_ACCESS = 'freeform-pro-exportProfilesAccess';
    public const PERMISSION_EXPORT_PROFILES_MANAGE = 'freeform-pro-exportProfilesManage';
    public const PERMISSION_EXPORT_NOTIFICATIONS_ACCESS = 'freeform-pro-exportNotificationsAccess';
    public const PERMISSION_EXPORT_NOTIFICATIONS_MANAGE = 'freeform-pro-exportNotificationsManage';

    public const EVENT_REGISTER_SUBNAV_ITEMS = 'registerSubnavItems';

    public bool $hasCpSettings = true;

    /**
     * @return Freeform|Plugin
     */
    public static function getInstance(): self
    {
        return parent::getInstance();
    }

    public static function isLocked(string $key, int $seconds): bool
    {
        return self::getInstance()->lock->isLocked($key, $seconds);
    }

    /**
     * {@inheritDoc}
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    /**
     * @param string $language
     */
    public static function t(string $message, array $params = [], string $language = null): string
    {
        return \Craft::t(self::TRANSLATION_CATEGORY, $message, $params, $language);
    }

    public function isPro(): bool
    {
        return self::EDITION_PRO === $this->edition;
    }

    public function isLite(): bool
    {
        return self::EDITION_LITE === $this->edition;
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if (!$this->isPro()) {
            throw new ForbiddenHttpException(self::t('Requires Freeform Pro'));
        }
    }

    /**
     * Includes CSS and JS files
     * Registers custom class auto-loader.
     */
    public function init()
    {
        parent::init();
        \Yii::setAlias('@freeform', __DIR__);

        // TODO: refactor these into separate bundles
        $this->initControllerMap();
        $this->initServices();
        $this->initRoutes();
        $this->initTwigVariables();
        $this->initFieldTypes();
        $this->initPermissions();
        $this->initEventListeners();
        $this->initHoneypot();
        $this->initConnections();
        $this->initBetaAssets();
        $this->initPaymentAssets();
        $this->initHookHandlers();
        $this->initCleanupJobs();
        $this->initTasks();
        $this->initBundles();

        if ($this->isPro() && $this->settings->getPluginName()) {
            $this->name = $this->settings->getPluginName();
        } else {
            $this->name = 'Freeform';
        }
    }

    public function getCpNavItem(): ?array
    {
        $navItem = parent::getCpNavItem();

        $subNavigation = include __DIR__.'/subnav.php';

        $event = new RegisterCpSubnavItemsEvent($subNavigation);
        $this->trigger(self::EVENT_REGISTER_SUBNAV_ITEMS, $event);

        $badgeCount = $this->settings->getBadgeCount();
        if ($badgeCount) {
            $navItem['badgeCount'] = $badgeCount;
        }
        $navItem['subnav'] = $event->getSubnavItems();

        return $navItem;
    }

    public function beforeUninstall(): void
    {
        $forms = $this->forms->getResolvedForms();
        foreach ($forms as $form) {
            \Craft::$app
                ->db
                ->createCommand()
                ->dropTableIfExists(Submission::getContentTableName($form))
                ->execute();
        }
    }

    /**
     * On install - insert default statuses.
     */
    public function afterInstall(): void
    {
        $fieldService = self::getInstance()->fields;

        $field = FieldModel::create();
        $field->handle = 'firstName';
        $field->label = 'First Name';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'lastName';
        $field->label = 'Last Name';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'email';
        $field->label = 'Email';
        $field->type = FieldInterface::TYPE_EMAIL;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'website';
        $field->label = 'Website';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'cellPhone';
        $field->label = 'Cell Phone';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'homePhone';
        $field->label = 'Home Phone';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'companyName';
        $field->label = 'Company Name';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'address';
        $field->label = 'Address';
        $field->setMetaProperty('rows', 2);
        $field->type = FieldInterface::TYPE_TEXTAREA;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'city';
        $field->label = 'City';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'state';
        $field->label = 'State';
        $field->type = FieldInterface::TYPE_SELECT;
        $field->setMetaProperty('options', include __DIR__.'/Resources/states.php');
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'zipCode';
        $field->label = 'Zip Code';
        $field->type = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'message';
        $field->label = 'Message';
        $field->type = FieldInterface::TYPE_TEXTAREA;
        $field->setMetaProperty('rows', 5);
        $fieldService->save($field);

        $field = FieldModel::create();
        $field->handle = 'number';
        $field->label = 'Number';
        $field->type = FieldInterface::TYPE_NUMBER;
        $fieldService->save($field);

        $status = StatusRecord::create();
        $status->name = 'Pending';
        $status->handle = 'pending';
        $status->color = 'light';
        $status->sortOrder = 1;
        $status->save();

        $status = StatusRecord::create();
        $status->name = 'Open';
        $status->handle = 'open';
        $status->color = 'green';
        $status->sortOrder = 2;
        $status->isDefault = 1;
        $status->save();

        $status = StatusRecord::create();
        $status->name = 'Closed';
        $status->handle = 'closed';
        $status->color = 'grey';
        $status->sortOrder = 3;
        $status->save();

        $field = FieldModel::create();
        $field->handle = 'payment';
        $field->label = '';
        $field->type = FieldInterface::TYPE_CREDIT_CARD_DETAILS;
        $fieldService->save($field);
    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return \Craft::$app->getView()->renderTemplate(
            'freeform/settings',
            ['settings' => $this->getSettings()]
        );
    }

    private function initControllerMap()
    {
        if (\Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'Solspace\\Freeform\\Commands';
        } else {
            $this->controllerMap = [
                'dashboard' => DashboardController::class,
                'api' => ApiController::class,
                'submit' => SubmitController::class,
                'codepack' => CodepackController::class,
                'crm' => CrmController::class,
                'mailing-lists' => MailingListsController::class,
                'payment-gateways' => PaymentGatewaysController::class,
                'fields' => FieldsController::class,
                'forms' => FormsController::class,
                'logs' => LogsController::class,
                'notifications-files' => FilesController::class,
                'notifications-database' => DatabaseController::class,
                'notifications-sender' => SenderController::class,
                'submissions' => SubmissionsController::class,
                'spam-submissions' => SpamSubmissionsController::class,
                'statuses' => StatusesController::class,
                'settings' => SettingsController::class,
                'resources' => ResourcesController::class,
                'quick-export' => QuickExportController::class,
                'export-profiles' => ExportProfilesController::class,
                'export-notifications' => ExportNotificationsController::class,
                'subscriptions' => SubscriptionsController::class,
                'payment-webhooks' => PaymentWebhooksController::class,
                'webhooks' => WebhooksController::class,
                'banners' => BannersController::class,
                'feeds' => FeedsController::class,
                'welcome-screen' => WelcomeScreenController::class,
                'rest-settings' => RESTSettingsController::class,
                'migrate-notifications' => MigrateNotificationsController::class,
                'file-upload' => FileUploadController::class,
                'diagnostics' => DiagnosticsController::class,
                'forms-rest' => FormsRESTController::class,
            ];
        }
    }

    private function initServices()
    {
        $this->setComponents(
            [
                'dashboard' => DashboardService::class,
                'crm' => CrmService::class,
                'charts' => ChartsService::class,
                'fields' => FieldsService::class,
                'files' => FilesService::class,
                'forms' => FormsService::class,
                'mailer' => MailerService::class,
                'mailingLists' => MailingListsService::class,
                'notifications' => NotificationsService::class,
                'settings' => SettingsService::class,
                'statuses' => StatusesService::class,
                'submissions' => SubmissionsService::class,
                'spamSubmissions' => SpamSubmissionsService::class,
                'logger' => LoggerService::class,
                'honeypot' => HoneypotService::class,
                'integrations' => IntegrationsService::class,
                'integrationsQueue' => IntegrationsQueueService::class,
                'paymentGateways' => PaymentGatewaysService::class,
                'connections' => ConnectionsService::class,
                'widgets' => WidgetsService::class,
                'export' => ExportService::class,
                'exportProfiles' => ExportProfilesService::class,
                'exportNotifications' => ExportNotificationsService::class,
                'rules' => RulesService::class,
                'paymentNotifications' => PaymentNotificationsService::class,
                'payments' => PaymentsService::class,
                'stripe' => StripeService::class,
                'subscriptionPlans' => SubscriptionPlansService::class,
                'subscriptions' => SubscriptionsService::class,
                'webhooks' => WebhooksService::class,
                'relations' => RelationsService::class,
                'notes' => NotesService::class,
                'payloadForwarding' => PayloadForwardingService::class,
                'digest' => DigestService::class,
                'summary' => SummaryService::class,
                'feed' => FreeformFeedService::class,
                'lock' => LockService::class,
                'diagnostics' => DiagnosticsService::class,
                'preflight' => PreflightService::class,
                'formTypes' => FormTypesService::class,
            ]
        );
    }

    private function initRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $routes = include __DIR__.'/routes.php';
                $event->rules = array_merge($event->rules, $routes);
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $routes = include __DIR__.'/site-routes.php';
                $event->rules = array_merge($event->rules, $routes);
            }
        );
    }

    private function initTwigVariables()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $event->sender->set('freeform', FreeformVariable::class);
                $event->sender->set('freeformServices', FreeformServicesVariable::class);
                $event->sender->set('freeformBanners', FreeformBannersVariable::class);
            }
        );

        \Craft::$app->view->registerTwigExtension(new FreeformTwigFilters());
    }

    private function initFieldTypes()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = FormFieldType::class;
                $event->types[] = SubmissionFieldType::class;
            }
        );
    }

    private function initPermissions()
    {
        if (\Craft::$app->getEdition() >= \Craft::Pro) {
            Event::on(
                UserPermissions::class,
                UserPermissions::EVENT_REGISTER_PERMISSIONS,
                function (RegisterUserPermissionsEvent $event) {
                    $forms = $this->forms->getAllFormNames();

                    $readPermissions = $managePermissions = $formPermissions = [];
                    foreach ($forms as $id => $name) {
                        $readKey = PermissionHelper::prepareNestedPermission(
                            self::PERMISSION_SUBMISSIONS_READ,
                            $id
                        );
                        $manageKey = PermissionHelper::prepareNestedPermission(
                            self::PERMISSION_SUBMISSIONS_MANAGE,
                            $id
                        );
                        $formPermissionName = PermissionHelper::prepareNestedPermission(
                            self::PERMISSION_FORMS_MANAGE,
                            $id
                        );

                        $readPermissions[$readKey] = ['label' => $name];
                        $managePermissions[$manageKey] = ['label' => $name];
                        $formPermissions[$formPermissionName] = ['label' => $name];
                    }

                    $permissions = [
                        self::PERMISSION_DASHBOARD_ACCESS => ['label' => self::t('Access Dashboard')],
                        self::PERMISSION_SUBMISSIONS_ACCESS => [
                            'label' => self::t('Access Submissions'),
                            'nested' => [
                                self::PERMISSION_SUBMISSIONS_READ => [
                                    'label' => self::t('Read All Submissions'),
                                    'info' => self::t("If you'd like to give users access to read all forms' submissions, check off this checkbox. It will also override any selections in the 'Read Submissions by Form' settings. 'Manage' permissions will also override any 'Read' permissions."),
                                ],
                                self::PERMISSION_SUBMISSIONS_READ_INDIVIDUAL => [
                                    'label' => self::t('Read Submissions by Form'),
                                    'info' => self::t("If you'd like to give users access to read only some forms' submissions, check off the ones here. These selections will be overridden by the 'Read All Submissions' checkbox. 'Manage' permissions will also override any 'Read' permissions."),
                                    'nested' => $readPermissions,
                                ],
                                self::PERMISSION_SUBMISSIONS_MANAGE => [
                                    'label' => self::t('Manage All Submissions'),
                                    'info' => self::t("If you'd like to give users access to manage all forms' submissions, check off this checkbox. It will also override any selections in the 'Manage Submissions by Form' settings. 'Manage' permissions will also override any 'Read' permissions."),
                                ],
                                self::PERMISSION_SUBMISSIONS_MANAGE_INDIVIDUAL => [
                                    'label' => self::t('Manage Submissions by Form'),
                                    'info' => self::t("If you'd like to give users access to manage only some forms' submissions, check off the ones here. These selections will be overridden by the 'Manage All Submissions' checkbox. 'Manage' permissions will also override any 'Read' permissions."),
                                    'nested' => $managePermissions,
                                ],
                            ],
                        ],
                        self::PERMISSION_FORMS_ACCESS => [
                            'label' => self::t('Access Forms'),
                            'nested' => [
                                self::PERMISSION_FORMS_CREATE => ['label' => self::t('Create New Forms')],
                                self::PERMISSION_FORMS_DELETE => ['label' => self::t('Delete Forms')],
                                self::PERMISSION_FORMS_MANAGE => [
                                    'label' => self::t('Manage All Forms'),
                                    'info' => self::t("If you'd like to give users access to all forms, check off this checkbox. It will also override any selections in the 'Manage Forms Individually' settings."),
                                ],
                                self::PERMISSION_FORMS_MANAGE_INDIVIDUAL => [
                                    'label' => self::t('Manage Forms Individually'),
                                    'info' => self::t("If you'd like to give users access to only some forms, check off the ones here. These selections will be overridden by the 'Manage All Forms' checkbox."),
                                    'nested' => $formPermissions,
                                ],
                            ],
                        ],
                        self::PERMISSION_FIELDS_ACCESS => [
                            'label' => self::t('Access Fields'),
                            'nested' => [
                                self::PERMISSION_FIELDS_MANAGE => ['label' => self::t('Manage Fields')],
                            ],
                        ],
                        self::PERMISSION_NOTIFICATIONS_ACCESS => [
                            'label' => self::t('Access Email Templates'),
                            'nested' => [
                                self::PERMISSION_NOTIFICATIONS_MANAGE => [
                                    'label' => self::t(
                                        'Manage Email Templates'
                                    ),
                                ],
                            ],
                        ],
                        self::PERMISSION_ACCESS_QUICK_EXPORT => ['label' => self::t('Access Quick Exporting')],
                        self::PERMISSION_EXPORT_PROFILES_ACCESS => [
                            'label' => self::t('Access Export Profiles'),
                            'nested' => [
                                self::PERMISSION_EXPORT_PROFILES_MANAGE => [
                                    'label' => self::t(
                                        'Manage Export Profiles'
                                    ),
                                ],
                            ],
                        ],
                        self::PERMISSION_EXPORT_NOTIFICATIONS_ACCESS => [
                            'label' => self::t('Access Export Notifications'),
                            'nested' => [
                                self::PERMISSION_EXPORT_NOTIFICATIONS_MANAGE => [
                                    'label' => self::t(
                                        'Manage Export Notifications'
                                    ),
                                ],
                            ],
                        ],
                        self::PERMISSION_SETTINGS_ACCESS => ['label' => self::t('Access Settings')],
                        self::PERMISSION_RESOURCES => ['label' => self::t('Access Resources')],
                    ];

                    $event->permissions[] = [
                        'heading' => $this->name,
                        'permissions' => $permissions,
                    ];
                }
            );
        }
    }

    private function initEventListeners()
    {
        Event::on(
            FormsService::class,
            FormsService::EVENT_RENDER_CLOSING_TAG,
            [$this->forms, 'addFormPluginScripts']
        );

        Event::on(
            Sites::class,
            Sites::EVENT_BEFORE_SAVE_SITE,
            function (SiteEvent $event) {
                if ($event->site->primary && (int) $event->site->id !== (int) $event->oldPrimarySiteId) {
                    $oldId = $event->oldPrimarySiteId;
                    $newId = $event->site->id;

                    $ids = (new Query())->select('[[id]]')->from('{{%elements}}')->where(
                        ['[[type]]' => Submission::class]
                    )->column();

                    \Craft::$app->db->createCommand()->update(
                        '{{%elements_sites}}',
                        ['siteId' => $newId],
                        ['siteId' => $oldId, 'elementId' => $ids]
                    )->execute();

                    \Craft::$app->db->createCommand()->update(
                        '{{%content}}',
                        ['siteId' => $newId],
                        ['siteId' => $oldId, 'elementId' => $ids]
                    )->execute();
                }
            }
        );

        Event::on(
            SettingsService::class,
            SettingsService::EVENT_REGISTER_SETTINGS_NAVIGATION,
            function (RegisterSettingsNavigationEvent $event) {
                if ($this->settings->isAllowAdminEdit()) {
                    $event->addNavigationItem('captchas', self::t('Captchas'), 'spam');
                }
            }
        );

        Event::on(
            SubmissionsService::class,
            SubmissionsService::EVENT_AFTER_SUBMIT,
            [$this->relations, 'relate']
        );

        if ($this->isPro()) {
            Event::on(
                FormsService::class,
                FormsService::EVENT_ATTACH_FORM_ATTRIBUTES,
                [$this->rules, 'addAttributesToFormTag']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_PAGE_JUMP,
                [$this->rules, 'handleFormPageJump']
            );

            Event::on(
                SubmissionsController::class,
                SubmissionsController::EVENT_REGISTER_EDIT_ASSETS,
                [$this->rules, 'registerRulesJsAsAssets']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_ATTACH_FORM_ATTRIBUTES,
                [$this->stripe, 'addAttributesToFormTag']
            );
        }
    }

    private function initHoneypot()
    {
        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this->honeypot, 'addHoneypotToJson']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_ATTACH_FORM_ATTRIBUTES,
            [$this->honeypot, 'addFormAttributes']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_RENDER_OPENING_TAG,
            [$this->honeypot, 'addHoneyPotInputToForm']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->honeypot, 'validateFormHoneypot']
        );
    }

    private function initConnections()
    {
        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->connections, 'validateConnections']
        );
    }

    private function initBetaAssets()
    {
        $disableFeedback = App::parseEnv('$FREEFORM_DISABLE_BETA_FEEDBACK_WIDGET');
        if ($disableFeedback && '$FREEFORM_DISABLE_BETA_FEEDBACK_WIDGET' !== $disableFeedback) {
            return;
        }

        $version = $this->getVersion();
        if (!preg_match('/alpha|beta/', $version)) {
            return;
        }

        $view = \Craft::$app->view;

        \Craft::$app->view->hook(
            'freeform-beta-widget',
            function (array $context) use ($view) {
                $view->registerAssetBundle(BetaBundle::class, View::POS_END);

                return $view->renderTemplate('freeform/_beta/feedback-widget');
            }
        );
    }

    private function initPaymentAssets()
    {
        if (!$this->isPro()) {
            return;
        }

        Event::on(
            SubmissionsController::class,
            SubmissionsController::EVENT_REGISTER_INDEX_ASSETS,
            function (RegisterEvent $event) {
                $event->getView()->registerAssetBundle(PaymentsBundle::class);
            }
        );

        Event::on(
            SubmissionsController::class,
            SubmissionsController::EVENT_REGISTER_EDIT_ASSETS,
            function (RegisterEvent $event) {
                $event->getView()->registerAssetBundle(PaymentsBundle::class);
            }
        );
    }

    private function initHookHandlers()
    {
        if (!$this->isPro()) {
            return;
        }

        SubmissionHookHandler::registerHooks();
        FormHookHandler::registerHooks();
    }

    private function initCleanupJobs()
    {
        if (!$this->isInstalled || \Craft::$app->request->getIsConsoleRequest()) {
            return;
        }

        if (self::isLocked(SettingsService::CACHE_KEY_PURGE, SettingsService::CACHE_TTL_SECONDS)) {
            return;
        }

        $assetAge = $this->settings->getPurgableUnfinalizedAssetAgeInMinutes();
        if ($assetAge > 0) {
            \Craft::$app->queue->push(new PurgeUnfinalizedAssetsJob(['age' => $assetAge]));
        }

        $submissionAge = $this->settings->getPurgableSubmissionAgeInDays();
        if ($submissionAge > 0) {
            \Craft::$app->queue->push(new PurgeSubmissionsJob(['age' => $submissionAge]));
        }

        $spamAge = $this->settings->getPurgableSpamAgeInDays();
        if ($spamAge > 0) {
            \Craft::$app->queue->push(new PurgeSpamJob(['age' => $spamAge]));
        }
    }

    private function initTasks()
    {
        if (!$this->isInstalled || \Craft::$app->request->getIsConsoleRequest()) {
            return;
        }

        Event::on(
            Application::class,
            Application::EVENT_AFTER_REQUEST,
            function () {
                if (!\Craft::$app->db->tableExists(FeedRecord::TABLE)) {
                    return;
                }

                self::getInstance()->feed->fetchFeed();
                self::getInstance()->digest->triggerDigest();
            }
        );
    }

    private function initBundles()
    {
        static $initialized;

        if (null === $initialized) {
            $classMap = ClassMapGenerator::createMap(__DIR__.'/Bundles');

            /** @var \ReflectionClass[][] $loadableClasses */
            $loadableClasses = [];

            /** @var BundleInterface $class */
            foreach ($classMap as $class => $path) {
                $reflectionClass = new \ReflectionClass($class);
                if (
                    $reflectionClass->implementsInterface(BundleInterface::class)
                    && !$reflectionClass->isAbstract()
                    && !$reflectionClass->isInterface()
                ) {
                    if ($class::isProOnly() && !$this->isPro()) {
                        continue;
                    }

                    $priority = $class::getPriority();
                    $loadableClasses[$priority][] = $reflectionClass;
                }
            }

            ksort($loadableClasses, \SORT_NUMERIC);

            foreach ($loadableClasses as $classes) {
                foreach ($classes as $class) {
                    $class->newInstance();
                }
            }

            $initialized = true;
        }
    }
}
