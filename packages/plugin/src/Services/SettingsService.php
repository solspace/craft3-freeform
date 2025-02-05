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

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationLoggerProvider;
use Solspace\Freeform\Events\Freeform\RegisterSettingsNavigationEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Single\Honeypot\Honeypot;
use Solspace\Freeform\Library\DataObjects\FormTemplate;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Library\Templates\TemplateLocator;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Notifications\Components\Recipients\Recipient;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use Solspace\Freeform\Services\Pro\DigestService;
use Symfony\Component\Finder\Finder;

class SettingsService extends BaseService
{
    public const CACHE_KEY_PURGE = 'freeform-purge-cache-key';
    public const CACHE_TTL_SECONDS = 60 * 60; // 1 hour

    public const EVENT_REGISTER_SETTINGS_NAVIGATION = 'registerSettingsNavigation';

    /** @var Settings */
    private static $settingsModel;

    public function getPluginName(): ?string
    {
        return $this->getSettingsModel()->pluginName;
    }

    public function isFreeformHoneypotEnabled(?Form $form = null): bool
    {
        $settingsModel = $this->getSettingsModel();

        if ($settingsModel->bypassSpamCheckOnLoggedInUsers && \Craft::$app->getUser()->id) {
            return false;
        }

        if ($form) {
            $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
            $honeypot = $integrationProvider->getSingleton($form, Honeypot::class);

            return (bool) $honeypot;
        }

        return false;
    }

    public function isSpamBehaviorSimulatesSuccess(): bool
    {
        return Settings::PROTECTION_SIMULATE_SUCCESS === $this->getSettingsModel()->spamProtectionBehavior;
    }

    public function isSpamBehaviorDisplayErrors(): bool
    {
        return Settings::PROTECTION_DISPLAY_ERRORS === $this->getSettingsModel()->spamProtectionBehavior;
    }

    public function isSpamBehaviorReloadForm(): bool
    {
        return Settings::PROTECTION_RELOAD_FORM === $this->getSettingsModel()->spamProtectionBehavior;
    }

    public function getFormTemplateDirectory(): ?string
    {
        return $this->getSettingsModel()->getAbsoluteFormTemplateDirectory();
    }

    public function getSuccessTemplateDirectory(): ?string
    {
        return $this->getSettingsModel()->getAbsoluteSuccessTemplateDirectory();
    }

    public function getSolspaceFormTemplateDirectory(): string
    {
        return __DIR__.'/../templates/_templates/formatting';
    }

    /**
     * @return FormTemplate[]
     *
     * @throws \InvalidArgumentException
     */
    public function getSolspaceFormTemplates(): array
    {
        return $this->getTemplatesIn($this->getSolspaceFormTemplateDirectory());
    }

    /**
     * @return FormTemplate[]
     */
    public function getCustomFormTemplates(): array
    {
        return $this->getTemplatesIn($this->getFormTemplateDirectory());
    }

    /**
     * @return FormTemplate[]
     */
    public function getSuccessTemplates(): array
    {
        $templates = [];
        $templateDirectoryPath = $this->getSuccessTemplateDirectory();
        if (!is_dir($templateDirectoryPath)) {
            return $templates;
        }

        $rootFiles = (new Finder())
            ->files()
            ->in($templateDirectoryPath)
            ->depth(0)
            ->sortByName()
            ->name('*.twig')
        ;

        foreach ($rootFiles as $file) {
            $templates[] = new FormTemplate($file->getRealPath(), $templateDirectoryPath);
        }

        return $templates;
    }

    public function isFooterScripts(): bool
    {
        return Settings::SCRIPT_INSERT_LOCATION_FOOTER === $this->getSettingsModel()->scriptInsertLocation;
    }

    public function isHeaderScripts(): bool
    {
        return Settings::SCRIPT_INSERT_LOCATION_HEADER === $this->getSettingsModel()->scriptInsertLocation;
    }

    public function isFormScripts(): bool
    {
        return Settings::SCRIPT_INSERT_LOCATION_FORM === $this->getSettingsModel()->scriptInsertLocation;
    }

    public function isManualScripts(): bool
    {
        return Settings::SCRIPT_INSERT_LOCATION_MANUAL === $this->getSettingsModel()->scriptInsertLocation;
    }

    public function scriptInsertType(): string
    {
        return $this->getSettingsModel()->scriptInsertType;
    }

    public function isFormSubmitDisable(): bool
    {
        return (bool) $this->getSettingsModel()->formSubmitDisable;
    }

    public function isRememberSubmitOrder(): bool
    {
        return (bool) $this->getSettingsModel()->rememberPageSubmitOrder;
    }

    public function isAutoScrollToErrors(): bool
    {
        return (bool) $this->getSettingsModel()->autoScrollToErrors;
    }

    public function isRemoveNewlines(): bool
    {
        return (bool) $this->getSettingsModel()->removeNewlines;
    }

    public function getPurgableSubmissionAgeInDays(): ?int
    {
        $age = $this->getSettingsModel()->purgableSubmissionAgeInDays;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return null;
        }

        return $age;
    }

    public function getPurgableSpamAgeInDays(): ?int
    {
        $age = $this->getSettingsModel()->purgableSpamAgeInDays;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return null;
        }

        return $age;
    }

    public function getPurgableUnfinalizedAssetAgeInMinutes(): ?int
    {
        $age = $this->getSettingsModel()->purgableUnfinalizedAssetAgeInMinutes;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return Settings::DEFAULT_UNFINALIZED_ASSET_AGE_MINUTES;
        }

        return $age;
    }

    public function isRenderFormHtmlInCpViews(): bool
    {
        return $this->getSettingsModel()->renderFormHtmlInCpViews;
    }

    public function getQueuePriority(): ?int
    {
        return $this->getSettingsModel()->queuePriority;
    }

    public function getSettingsModel(): Settings
    {
        if (null === self::$settingsModel) {
            $plugin = Freeform::getInstance();
            self::$settingsModel = $plugin->getSettings();
        }

        return self::$settingsModel;
    }

    public function getSettingsNavigation(): array
    {
        $errorCount = Freeform::getInstance()->logger->getLogReader()->count();
        $integrationsCount = Freeform::getInstance()->logger->getLogReader(IntegrationLoggerProvider::LOG_FILE)->count();
        $emailCount = Freeform::getInstance()->logger->getLogReader(NotificationLoggerProvider::LOG_FILE)->count();

        $nav = [
            'general' => ['title' => Freeform::t('General Settings')],
            'form-behavior' => ['title' => Freeform::t('Form Behavior')],
            'form-builder' => ['title' => Freeform::t('Form Builder')],
            'limited-users' => ['title' => Freeform::t('Limited Users')],
            'template-manager' => ['title' => Freeform::t('Template Manager')],
            'pdf-templates' => ['title' => Freeform::t('PDF Templates')],
            'statuses' => ['title' => Freeform::t('Statuses')],
            'demo-templates' => ['title' => Freeform::t('Demo Templates')],
            'spam' => ['title' => Freeform::t('Spam Protection')],
            'hdapi' => ['heading' => Freeform::t('Integrations')],
            'integrations/email-marketing' => ['title' => Freeform::t('Email Marketing')],
            'integrations/crm' => ['title' => Freeform::t('CRM')],
            'integrations/elements' => ['title' => Freeform::t('Elements')],
            'integrations/captchas' => ['title' => Freeform::t('Captchas')],
            'integrations/spam-blocking' => ['title' => Freeform::t('Spam Blocking')],
            'integrations/payment-gateways' => ['title' => Freeform::t('Payments')],
            'integrations/webhooks' => ['title' => Freeform::t('Webhooks')],
            'integrations/single' => ['title' => Freeform::t('Single')],
            'integrations/other' => ['title' => Freeform::t('Other')],
            'hdalerts' => ['heading' => Freeform::t('Reliability')],
            'notices-and-alerts' => ['title' => Freeform::t('Notices & Alerts')],
            'diagnostics' => ['title' => Freeform::t('Diagnostics')],
            'hdlogs' => ['heading' => Freeform::t('Logs')],
            'error-log' => ['title' => Freeform::t('Errors <span class="badge">{count}</span>', ['count' => $errorCount])],
            'integrations-log' => ['title' => Freeform::t('Integrations <span class="badge">{count}</span>', ['count' => $integrationsCount])],
            'email-log' => ['title' => Freeform::t('Emails <span class="badge">{count}</span>', ['count' => $emailCount])],
        ];

        if (!$this->isAllowAdminEdit()) {
            unset($nav['hdspam']);
            foreach ($nav as $key => $value) {
                if (!isset($value['heading']) && $this->isSectionASetting($key)) {
                    unset($nav[$key]);
                }
            }
        }

        $nav = array_filter($nav);

        $event = new RegisterSettingsNavigationEvent($nav);
        $this->trigger(self::EVENT_REGISTER_SETTINGS_NAVIGATION, $event);

        return $event->getNavigation();
    }

    public function isSpamFolderEnabled(): bool
    {
        return $this->getSettingsModel()->spamFolderEnabled;
    }

    public function isAjaxEnabledByDefault(): bool
    {
        return $this->getSettingsModel()->defaults->settings->processing->ajax->getValue();
    }

    public function isSectionASetting(string $sectionName): bool
    {
        $nonSettingSections = [
            'limited-users',
            'statuses',
            'pdf-templates',
            'error-log',
            'integrations-log',
            'email-log',
            'diagnostics',
            'integrations/email-marketing',
            'integrations/crm',
            'integrations/payment-gateways',
            'integrations/captchas',
            'integrations/spam-blocking',
            'integrations/elements',
            'integrations/webhooks',
            'integrations/single',
            'integrations/other',
        ];

        return !\in_array($sectionName, $nonSettingSections, true);
    }

    public function isAllowAdminEdit(): bool
    {
        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            return \Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
        }

        return true;
    }

    public function getFailedNotificationRecipients(): RecipientCollection
    {
        return $this->getRecipientCollection($this->getSettingsModel()->alertNotificationRecipients ?? '');
    }

    public function getDigestRecipients(): RecipientCollection
    {
        return $this->getRecipientCollection($this->getSettingsModel()->digestRecipients ?? '');
    }

    public function getDigestFrequency(): int
    {
        return (int) ($this->getSettingsModel()->digestFrequency ?? DigestService::FREQUENCY_WEEKLY_MONDAYS);
    }

    public function getClientDigestRecipients(): RecipientCollection
    {
        return $this->getRecipientCollection($this->getSettingsModel()->clientDigestRecipients ?? '');
    }

    public function getClientDigestFrequency(): int
    {
        return (int) ($this->getSettingsModel()->clientDigestFrequency ?? DigestService::FREQUENCY_WEEKLY_MONDAYS);
    }

    public function isDigestOnlyOnProduction(): bool
    {
        return $this->getSettingsModel()->digestOnlyOnProduction;
    }

    public function getBadgeCount(): ?int
    {
        $type = $this->getSettingsModel()->badgeType;
        if (!$type) {
            return null;
        }

        $freeform = Freeform::getInstance();
        if ('submissions' === $type) {
            return $freeform->submissions->getSubmissionCount();
        }

        if ('spam' === $type) {
            return $freeform->spamSubmissions->getSubmissionCount(null, null, true);
        }

        $total = 0;
        if ('all' === $type || 'notices' === $type) {
            $total += $freeform->feed->getUnreadCount();
        }

        if ('all' === $type || 'errors' === $type) {
            $total += $freeform->logger->getLogReader()->count();
        }

        return $total;
    }

    public function saveSettings(array $data): bool
    {
        $plugin = Freeform::getInstance();
        $plugin->setSettings($data);

        return \Craft::$app->plugins->savePluginSettings($plugin, $data);
    }

    public function getPluginJsPath(): string
    {
        return 'js/scripts/front-end/plugin/freeform.js';
    }

    public function getPluginCssPath(): string
    {
        return 'css/front-end/plugin/freeform.css';
    }

    public function isFormFieldShowOnlyAllowedForms(): bool
    {
        return $this->getSettingsModel()->formFieldShowOnlyAllowedForms;
    }

    public function isNotificationQueueEnabled(): bool
    {
        return $this->getSettingsModel()->useQueueForEmailNotifications;
    }

    public function isIntegrationQueueEnabled(): bool
    {
        return $this->getSettingsModel()->useQueueForIntegrations;
    }

    private function getTemplatesIn(?string $path): array
    {
        if (!$path) {
            return [];
        }

        $locator = \Craft::$container->get(TemplateLocator::class);

        return $locator->locate($path);
    }

    private function getRecipientCollection(string $emails): RecipientCollection
    {
        $recipients = array_map(
            fn (string $email) => new Recipient($email),
            StringHelper::extractSeparatedValues($emails)
        );

        return new RecipientCollection($recipients);
    }
}
