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

use Solspace\Commons\Helpers\StringHelper;
use Solspace\Freeform\Events\Freeform\RegisterSettingsNavigationEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\DataObjects\FormTemplate;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Services\Pro\DigestService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SettingsService extends BaseService
{
    public const CACHE_KEY_PURGE = 'freeform-purge-cache-key';
    public const CACHE_TTL_SECONDS = 60 * 60; // 1 hour

    public const EVENT_REGISTER_SETTINGS_NAVIGATION = 'registerSettingsNavigation';

    /** @var Settings */
    private static $settingsModel;

    /**
     * @return null|string
     */
    public function getPluginName()
    {
        return $this->getSettingsModel()->pluginName;
    }

    /**
     * @return null|string
     */
    public function getCustomErrorMessage()
    {
        return $this->getSettingsModel()->customErrorMessage;
    }

    public function isFreeformHoneypotEnabled(Form $form = null): bool
    {
        $enabled = $this->getSettingsModel()->freeformHoneypot;
        if (!$enabled) {
            return false;
        }

        if ($form && $form->getPropertyBag()->get(HoneypotService::HONEYPOT_DISABLE_KEY, false)) {
            return false;
        }

        return true;
    }

    public function isFreeformHoneypotEnhanced(): bool
    {
        return (bool) $this->getSettingsModel()->freeformHoneypotEnhancement;
    }

    public function isSpamBehaviourSimulatesSuccess(): bool
    {
        return Settings::PROTECTION_SIMULATE_SUCCESS === $this->getSettingsModel()->spamProtectionBehaviour;
    }

    public function isSpamBehaviourDisplayErrors(): bool
    {
        return Settings::PROTECTION_DISPLAY_ERRORS === $this->getSettingsModel()->spamProtectionBehaviour;
    }

    public function isSpamBehaviourReloadForm(): bool
    {
        return Settings::PROTECTION_RELOAD_FORM === $this->getSettingsModel()->spamProtectionBehaviour;
    }

    public function getFieldDisplayOrder(): string
    {
        return $this->getSettingsModel()->fieldDisplayOrder;
    }

    public function getFormTemplateDirectory(): string
    {
        return $this->getSettingsModel()->formTemplateDirectory;
    }

    public function getSolspaceFormTemplateDirectory(): string
    {
        return __DIR__.'/../templates/_defaultFormTemplates';
    }

    /**
     * Mark the tutorial as finished.
     */
    public function finishTutorial(): bool
    {
        $plugin = Freeform::getInstance();
        if (\Craft::$app->plugins->savePluginSettings($plugin, ['showTutorial' => false])) {
            return true;
        }

        return false;
    }

    /**
     * @return FormTemplate[]
     *
     * @throws \InvalidArgumentException
     */
    public function getSolspaceFormTemplates(): array
    {
        $templateDirectoryPath = $this->getSolspaceFormTemplateDirectory();

        $fs = new Finder();

        /** @var SplFileInfo[] $fileIterator */
        $fileIterator = $fs
            ->files()
            ->in($templateDirectoryPath)
            ->sortByName()
            ->name('*.html')
            ->name('*.twig')
        ;

        $templates = [];

        foreach ($fileIterator as $file) {
            $templates[] = new FormTemplate($file->getRealPath());
        }

        return $templates;
    }

    /**
     * @return FormTemplate[]
     */
    public function getCustomFormTemplates(): array
    {
        $templates = [];
        foreach ($this->getSettingsModel()->listTemplatesInFormTemplateDirectory() as $path => $name) {
            $templates[] = new FormTemplate($path);
        }

        return $templates;
    }

    /**
     * @return FormTemplate[]
     */
    public function getSuccessTemplates(): array
    {
        $templates = [];
        foreach ($this->getSettingsModel()->listTemplatesInSuccessTemplateDirectory() as $path => $name) {
            $templates[] = new FormTemplate($path);
        }

        return $templates;
    }

    public function isFooterScripts(): bool
    {
        return Settings::SCRIPT_INSERT_LOCATION_FOOTER === $this->getSettingsModel()->scriptInsertLocation;
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

    /**
     * @return null|int
     */
    public function getPurgableSubmissionAgeInDays()
    {
        $age = $this->getSettingsModel()->purgableSubmissionAgeInDays;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return null;
        }

        return (int) $age;
    }

    /**
     * @return null|int
     */
    public function getPurgableSpamAgeInDays()
    {
        $age = $this->getSettingsModel()->purgableSpamAgeInDays;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return null;
        }

        return (int) $age;
    }

    /**
     * @return null|int
     */
    public function getPurgableUnfinalizedAssetAgeInMinutes()
    {
        $age = $this->getSettingsModel()->purgableUnfinalizedAssetAgeInMinutes;

        if (null === $age || '' === $age || (int) $age <= 0) {
            return Settings::DEFAULT_UNFINALIZED_ASSET_AGE_MINUTES;
        }

        return (int) $age;
    }

    public function isRenderFormHtmlInCpViews(): bool
    {
        return $this->getSettingsModel()->renderFormHtmlInCpViews;
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

        $nav = [
            'general' => ['title' => Freeform::t('General Settings')],
            'form-behavior' => ['title' => Freeform::t('Form Behavior')],
            'form-builder' => ['title' => Freeform::t('Form Builder')],
            'formatting-templates' => ['title' => Freeform::t('Formatting Templates')],
            'email-templates' => ['title' => Freeform::t('Email Templates')],
            'success-templates' => ['title' => Freeform::t('Success Templates')],
            'statuses' => ['title' => Freeform::t('Statuses')],
            'demo-templates' => ['title' => Freeform::t('Demo Templates')],
            'hdspam' => ['heading' => Freeform::t('Spam')],
            'spam' => ['title' => Freeform::t('Spam Settings')],
            'hdapi' => ['heading' => Freeform::t('Integrations')],
            'mailing-lists' => ['title' => Freeform::t('Email Marketing')],
            'crm' => ['title' => Freeform::t('CRM')],
            'payment-gateways' => ['title' => Freeform::t('Payments')],
            'webhooks' => ['title' => Freeform::t('Webhooks')],
            'hdalerts' => ['heading' => Freeform::t('Reliability')],
            'notices-and-alerts' => ['title' => Freeform::t('Notices & Alerts')],
            'error-log' => ['title' => Freeform::t('Error Log <span class="badge">{count}</span>', ['count' => $errorCount])],
            'diagnostics' => ['title' => Freeform::t('Diagnostics')],
            'craft-preflight' => ['title' => Freeform::t('Craft 4 Preflight')],
        ];

        if (version_compare(Freeform::getInstance()->getVersion(), '4.0.0-alpha', '>=')) {
            unset($nav['craft-preflight']);
        }

        if (!$this->isAllowAdminEdit()) {
            unset($nav['hdspam']);
            foreach ($nav as $key => $value) {
                if (!isset($value['heading']) && $this->isSectionASetting($key)) {
                    unset($nav[$key]);
                }
            }
        }

        $event = new RegisterSettingsNavigationEvent($nav);

        $this->trigger(self::EVENT_REGISTER_SETTINGS_NAVIGATION, $event);

        return $event->getNavigation();
    }

    public function isSpamFolderEnabled(): bool
    {
        return (bool) $this->getSettingsModel()->spamFolderEnabled;
    }

    public function isAjaxEnabledByDefault(): bool
    {
        return (bool) $this->getSettingsModel()->ajaxByDefault;
    }

    public function isSectionASetting(string $sectionName): bool
    {
        $nonSettingSections = [
            'statuses',
            'error-log',
            'diagnostics',
            'mailing-lists',
            'crm',
            'payment-gateways',
            'webhooks',
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

    public function isOldFreeformInstalled(): bool
    {
        $paths = [
            '/solspace/craft3-freeform',
            '/solspace/craft3-freeform-pro',
            '/solspace/craft3-freeform-payments',
        ];

        if (\defined('CRAFT_VENDOR_PATH')) {
            $vendorPath = CRAFT_VENDOR_PATH;
        } elseif (\defined('CRAFT_BASE_PATH')) {
            $vendorPath = CRAFT_BASE_PATH.'/vendor';
        } else {
            return false;
        }

        $hasOldFreeform = false;
        foreach ($paths as $path) {
            if (is_dir($vendorPath.$path)) {
                $hasOldFreeform = true;
            }
        }

        return $hasOldFreeform;
    }

    public function getFailedNotificationRecipients(): array
    {
        return StringHelper::extractSeparatedValues($this->getSettingsModel()->alertNotificationRecipients ?? '');
    }

    public function getDigestRecipients(): array
    {
        return StringHelper::extractSeparatedValues($this->getSettingsModel()->digestRecipients ?? '');
    }

    public function getDigestFrequency(): int
    {
        return (int) ($this->getSettingsModel()->digestFrequency ?? DigestService::FREQUENCY_WEEKLY_MONDAYS);
    }

    public function getClientDigestRecipients(): array
    {
        return StringHelper::extractSeparatedValues($this->getSettingsModel()->clientDigestRecipients ?? '');
    }

    public function getClientDigestFrequency(): int
    {
        return (int) ($this->getSettingsModel()->clientDigestFrequency ?? DigestService::FREQUENCY_WEEKLY_MONDAYS);
    }

    public function isDigestOnlyOnProduction(): bool
    {
        return (bool) $this->getSettingsModel()->digestOnlyOnProduction;
    }

    public function getBadgeCount()
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

    public function saveSettings(array $data)
    {
        $plugin = Freeform::getInstance();
        $plugin->setSettings($data);

        return \Craft::$app->plugins->savePluginSettings($plugin, $data);
    }

    public function getPluginJsPath(): string
    {
        return \Yii::getAlias('@freeform/Resources/js/scripts/front-end/plugin/freeform.js');
    }

    public function getPluginCssPath(): string
    {
        return \Yii::getAlias('@freeform/Resources/css/front-end/plugin/freeform.css');
    }

    public function isFormFieldShowOnlyAllowedForms(): bool
    {
        return (bool) $this->getSettingsModel()->formFieldShowOnlyAllowedForms;
    }
}
