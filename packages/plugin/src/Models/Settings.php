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

namespace Solspace\Freeform\Models;

use craft\base\Model;
use craft\helpers\FileHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\Form\Defaults\Defaults;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Services\Pro\DigestService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Settings extends Model
{
    public const PROTECTION_SIMULATE_SUCCESS = 'simulate_success';
    public const PROTECTION_DISPLAY_ERRORS = 'display_errors';
    public const PROTECTION_RELOAD_FORM = 'reload_form';

    public const DEFAULT_BLOCKED_EMAILS_ERROR_MESSAGE = 'Invalid Email Address';
    public const DEFAULT_BLOCKED_KEYWORDS_ERROR_MESSAGE = 'Invalid Entry Data';

    public const EMAIL_TEMPLATE_STORAGE_TYPE_FILES = 'files';
    public const EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE = 'database';
    public const EMAIL_TEMPLATE_STORAGE_TYPE_BOTH = 'files_database';

    public const THROTTLING_TIME_FRAME_MINUTES = 'm';
    public const THROTTLING_TIME_FRAME_SECONDS = 's';

    public const SCRIPT_INSERT_LOCATION_HEADER = 'header';
    public const SCRIPT_INSERT_LOCATION_FOOTER = 'footer';
    public const SCRIPT_INSERT_LOCATION_FORM = 'form';
    public const SCRIPT_INSERT_LOCATION_MANUAL = 'manual';

    /** @deprecated No longer used since Freeform v5.2.3. Use SCRIPT_INSERT_TYPE_FILES instead. */
    public const SCRIPT_INSERT_TYPE_POINTERS = 'pointers';
    public const SCRIPT_INSERT_TYPE_FILES = 'files';
    public const SCRIPT_INSERT_TYPE_INLINE = 'inline';

    public const CONTEXT_TYPE_PAYLOAD = 'payload';
    public const CONTEXT_TYPE_SESSION = 'session';
    public const CONTEXT_TYPE_DATABASE = 'database';

    public const DEFAULT_AJAX = true;
    public const DEFAULT_FORMATTING_TEMPLATE = 'basic-light/index.twig';

    public const DEFAULT_ACTIVE_SESSION_ENTRIES = 50;
    public const DEFAULT_SESSION_ENTRY_TTL = 10800; // 3 hours

    public const DEFAULT_UNFINALIZED_ASSET_AGE_MINUTES = 180;

    public const SAVE_FORM_TTL = 30;
    public const SAVE_FORM_SESSION_LIMIT = 10;

    public const LOGGING_LEVEL_ERROR = 'error';
    public const LOGGING_LEVEL_INFO = 'info';
    public const LOGGING_LEVEL_DEBUG = 'debug';

    /** @var string */
    public $pluginName;

    /** @var string */
    public $formTemplateDirectory;

    /** @var bool */
    public $allowFileTemplateEdit;

    /** @var string */
    public $emailTemplateDirectory;

    /** @var string */
    public $emailTemplateStorageType;

    /** @var string */
    public $emailTemplateDefault;

    /** @var string */
    public $successTemplateDirectory;

    /** @var string */
    public $defaultView;

    /** @var bool */
    public $removeNewlines;

    /** @var bool */
    public $exportLabels;

    /** @var bool */
    public $exportHandlesAsNames;

    /** @deprecated use $scriptInsertLocation instead */
    public $footerScripts;

    /** @var string */
    public $scriptInsertLocation;

    /** @var string */
    public $scriptInsertType;

    /** @var bool */
    public $formSubmitDisable;

    /** @var bool */
    public $rememberPageSubmitOrder;

    /** @var int */
    public $formSubmitExpiration;

    /** @var int */
    public $minimumSubmitTime;

    /** @var string */
    public $spamProtectionBehavior;

    /** @var int */
    public $submissionThrottlingCount;

    /** @var string */
    public $submissionThrottlingTimeFrame;

    /**
     * @var string
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $blockedEmails;

    /**
     * @var string
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $blockedKeywords;

    /**
     * @var string
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $blockedKeywordsError;

    /**
     * @var string
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $blockedEmailsError;

    /**
     * @var bool
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $showErrorsForBlockedEmails;

    /**
     * @var bool
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $showErrorsForBlockedKeywords;

    /**
     * @var string
     *
     * @deprecated No longer used in Freeform 5.4.1. This is achieved by creating spam block integrations.
     */
    public $blockedIpAddresses;

    /** @var int */
    public $purgableSubmissionAgeInDays;

    /** @var int */
    public $purgableSpamAgeInDays;

    /** @var int */
    public $purgableUnfinalizedAssetAgeInMinutes;
    public bool $purgeAssets = true;

    /** @var bool */
    public $spamFolderEnabled;

    /** @var bool */
    public $renderFormHtmlInCpViews;

    /** @var bool */
    public $autoScrollToErrors;

    /** @var bool */
    public $autoScroll;

    /** @var bool */
    public $fillWithGet;

    /** @var string */
    public $formattingTemplate;

    /** @var int */
    public $sessionEntryMaxCount;

    /** @var int */
    public $sessionEntryTTL;

    /** @var string */
    public $alertNotificationRecipients;

    /** @var string */
    public $digestRecipients;

    /** @var string */
    public $digestFrequency;

    /** @var string */
    public $clientDigestRecipients;

    /** @var string */
    public $clientDigestFrequency;

    /** @var bool */
    public $digestOnlyOnProduction;

    /** @var array */
    public $displayFeed;

    /** @var string */
    public $badgeType;

    /** @var bool */
    public $updateSearchIndexes;

    /** @var bool */
    public $formFieldShowOnlyAllowedForms;

    /** @var string */
    public $sessionContext;

    /** @var int */
    public $sessionContextTimeToLiveMinutes;

    /** @var int */
    public $sessionContextCount;

    /** @var string */
    public $sessionContextSecret;

    /** @var int */
    public $saveFormTtl;

    /** @var int */
    public $saveFormSessionLimit;

    /** @var bool */
    public $bypassSpamCheckOnLoggedInUsers;

    public ?int $queuePriority = null;
    public array $hiddenFieldTypes = [];
    public array $surveys = [];
    public bool $allowDashesInFieldHandles = false;
    public bool $sitesEnabled = false;
    public string $defaultFromEmail = "{{ craft.app.projectConfig.get('email.fromEmail') }}";
    public string $defaultFromName = "{{ craft.app.projectConfig.get('email.fromName') }}";
    public Defaults $defaults;
    public bool $useQueueForEmailNotifications = false;
    public bool $useQueueForIntegrations = false;
    public string $loggingLevel = self::LOGGING_LEVEL_ERROR;

    /**
     * Settings constructor.
     */
    public function __construct(array $config = [])
    {
        $this->pluginName = null;
        $this->formTemplateDirectory = null;
        $this->successTemplateDirectory = null;
        $this->defaultView = Freeform::VIEW_FORMS;
        $this->removeNewlines = false;
        $this->exportLabels = false;
        $this->exportHandlesAsNames = false;
        $this->footerScripts = false;
        $this->scriptInsertLocation = self::SCRIPT_INSERT_LOCATION_FOOTER;
        $this->scriptInsertType = self::SCRIPT_INSERT_TYPE_FILES;
        $this->formSubmitDisable = true;
        $this->rememberPageSubmitOrder = true;

        $this->spamProtectionBehavior = self::PROTECTION_SIMULATE_SUCCESS;
        $this->blockedEmails = null;
        $this->blockedKeywords = null;
        $this->blockedEmailsError = self::DEFAULT_BLOCKED_EMAILS_ERROR_MESSAGE;
        $this->blockedKeywordsError = self::DEFAULT_BLOCKED_KEYWORDS_ERROR_MESSAGE;
        $this->blockedIpAddresses = null;
        $this->showErrorsForBlockedKeywords = false;
        $this->showErrorsForBlockedEmails = false;
        $this->spamFolderEnabled = true;
        $this->submissionThrottlingCount = null;
        $this->submissionThrottlingTimeFrame = null;
        $this->purgableSubmissionAgeInDays = null;
        $this->purgableSpamAgeInDays = null;
        $this->purgableUnfinalizedAssetAgeInMinutes = self::DEFAULT_UNFINALIZED_ASSET_AGE_MINUTES;
        $this->renderFormHtmlInCpViews = true;
        $this->autoScrollToErrors = true;
        $this->autoScroll = true;
        $this->fillWithGet = false;
        $this->formattingTemplate = self::DEFAULT_FORMATTING_TEMPLATE;
        $this->alertNotificationRecipients = null;
        $this->digestRecipients = null;
        $this->digestFrequency = DigestService::FREQUENCY_WEEKLY_MONDAYS;
        $this->clientDigestRecipients = null;
        $this->clientDigestFrequency = DigestService::FREQUENCY_WEEKLY_MONDAYS;
        $this->digestOnlyOnProduction = false;
        $this->displayFeed = true;
        $this->badgeType = 'all';

        $this->allowFileTemplateEdit = true;
        $this->emailTemplateDirectory = null;
        $this->emailTemplateStorageType = self::EMAIL_TEMPLATE_STORAGE_TYPE_BOTH;
        $this->emailTemplateDefault = self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE;

        $this->sessionEntryMaxCount = self::DEFAULT_ACTIVE_SESSION_ENTRIES;
        $this->sessionEntryTTL = self::DEFAULT_SESSION_ENTRY_TTL;

        $this->updateSearchIndexes = true;

        $this->formFieldShowOnlyAllowedForms = false;

        $this->sessionContext = self::CONTEXT_TYPE_PAYLOAD;
        $this->sessionContextTimeToLiveMinutes = 180;
        $this->sessionContextCount = 100;
        $this->sessionContextSecret = '';

        $this->saveFormTtl = self::SAVE_FORM_TTL;
        $this->saveFormSessionLimit = self::SAVE_FORM_SESSION_LIMIT;

        $this->bypassSpamCheckOnLoggedInUsers = false;

        $this->hiddenFieldTypes = [];

        $this->allowDashesInFieldHandles = false;

        $this->defaults = new Defaults($config['defaults'] ?? []);
        unset($config['defaults']);

        parent::__construct($config);
    }

    public function prepareFolderStructure(): void
    {
        $templatesPath = \Craft::$app->path->getSiteTemplatesPath();
        $formattingPath = FileHelper::absolutePath($this->formTemplateDirectory, $templatesPath);
        if (!is_dir($formattingPath)) {
            FileHelper::createDirectory($formattingPath, 0777);
        }

        $emailTemplatesPath = FileHelper::absolutePath($this->emailTemplateDirectory, $templatesPath);
        if (!is_dir($emailTemplatesPath)) {
            FileHelper::createDirectory($emailTemplatesPath, 0777);
        }

        $successTemplatesPath = FileHelper::absolutePath($this->successTemplateDirectory, $templatesPath);
        if (!is_dir($successTemplatesPath)) {
            FileHelper::createDirectory($successTemplatesPath, 0777);
        }
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (\array_key_exists('defaults', $values)) {
            $values['defaults'] = new Defaults($values['defaults']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function rules(): array
    {
        return [
            ['formTemplateDirectory', 'folderExists'],
        ];
    }

    public function folderExists(string $attribute)
    {
        $path = $this->{$attribute};
        $absolutePath = $this->getAbsolutePath($path);

        if (!file_exists($absolutePath)) {
            $this->addError(
                $attribute,
                Freeform::t(
                    'Directory "{directory}" does not exist',
                    ['directory' => $absolutePath]
                )
            );
        }
    }

    /**
     * If a form template directory has been set and it exists - return its absolute path.
     *
     * @return null|string
     */
    public function getAbsoluteFormTemplateDirectory()
    {
        if ($this->formTemplateDirectory) {
            $absolutePath = $this->getAbsolutePath($this->formTemplateDirectory);

            return file_exists($absolutePath) ? $absolutePath : null;
        }

        return null;
    }

    public function canManageEmailTemplates(): bool
    {
        $canEditTemplates = self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE === $this->emailTemplateStorageType
            || (self::EMAIL_TEMPLATE_STORAGE_TYPE_BOTH === $this->emailTemplateStorageType && self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE === $this->emailTemplateDefault)
            || ($this->getAbsoluteEmailTemplateDirectory() && $this->allowFileTemplateEdit);

        return PermissionHelper::checkPermission(Freeform::PERMISSION_NOTIFICATIONS_MANAGE)
            && $canEditTemplates;
    }

    /**
     * If an email template directory has been set and it exists - return its absolute path.
     *
     * @return null|string
     */
    public function getAbsoluteEmailTemplateDirectory()
    {
        if ($this->emailTemplateDirectory) {
            $absolutePath = $this->getAbsolutePath($this->emailTemplateDirectory);

            return file_exists($absolutePath) ? $absolutePath : null;
        }

        return null;
    }

    public function getEmailStorageTypeName(): string
    {
        return match ($this->emailTemplateStorageType) {
            self::EMAIL_TEMPLATE_STORAGE_TYPE_FILES => 'File',
            self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE => 'Database',
            self::EMAIL_TEMPLATE_STORAGE_TYPE_BOTH => 'File & Database',
            default => 'Not set',
        };
    }

    public function getEmailTemplateDefault(): string
    {
        return match ($this->emailTemplateStorageType) {
            self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE => self::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE,
            self::EMAIL_TEMPLATE_STORAGE_TYPE_FILES => self::EMAIL_TEMPLATE_STORAGE_TYPE_FILES,
            default => $this->emailTemplateDefault,
        };
    }

    public function getAbsoluteSuccessTemplateDirectory()
    {
        if ($this->successTemplateDirectory) {
            $absolutePath = $this->getAbsolutePath($this->successTemplateDirectory);

            return file_exists($absolutePath) ? $absolutePath : null;
        }

        return null;
    }

    public function cloneDemoTemplateContent(string $name, string $destination): void
    {
        $source = __DIR__."/../templates/_templates/formatting/{$name}";
        if (!file_exists($source) || !is_dir($source)) {
            throw new FreeformException(
                Freeform::t('Could not get demo template content. Please contact Solspace.')
            );
        }

        FileHelper::copyDirectory($source, $destination);
    }

    /**
     * Gets the default email template content.
     *
     * @throws FreeformException
     */
    public function getEmailTemplateContent(): string
    {
        $path = __DIR__.'/../templates/_templates/email/default.twig';
        if (!file_exists($path)) {
            throw new FreeformException(
                Freeform::t(
                    'Could not get email template content. Please contact Solspace.'
                )
            );
        }

        $email = $this->defaultFromEmail ?: "{{ craft.app.projectConfig.get('email.fromEmail') }}";
        $name = $this->defaultFromName ?: "{{ craft.app.projectConfig.get('email.fromName') }}";

        return str_replace(
            ['__placeholderFromEmail__', '__placeholderFromName__'],
            [$email, $name],
            file_get_contents($path)
        );
    }

    public function getSuccessTemplateContent(): string
    {
        $path = __DIR__.'/../templates/_templates/success/default.twig';
        if (!file_exists($path)) {
            throw new FreeformException(
                Freeform::t(
                    'Could not get success template content. Please contact Solspace.'
                )
            );
        }

        return file_get_contents($path);
    }

    /**
     * @return array|bool
     */
    public function listTemplatesInFormTemplateDirectory(): array
    {
        return $this->getTemplatesInDirectory($this->getAbsoluteFormTemplateDirectory());
    }

    /**
     * @return array|bool
     */
    public function listTemplatesInEmailTemplateDirectory(): array
    {
        return $this->getTemplatesInDirectory($this->getAbsoluteEmailTemplateDirectory());
    }

    /**
     * @return array|bool
     */
    public function listTemplatesInSuccessTemplateDirectory(): array
    {
        return $this->getTemplatesInDirectory($this->getAbsoluteSuccessTemplateDirectory());
    }

    public function getBlockedKeywords(): array
    {
        return $this->getArrayFromDelimitedText($this->blockedKeywords);
    }

    public function getBlockedKeywordsError(): string
    {
        return $this->blockedKeywordsError ?? self::DEFAULT_BLOCKED_KEYWORDS_ERROR_MESSAGE;
    }

    public function getBlockedEmails(): array
    {
        return $this->getArrayFromDelimitedText($this->blockedEmails);
    }

    public function getBlockedIpAddresses(): array
    {
        return $this->getArrayFromDelimitedText($this->blockedIpAddresses);
    }

    public function getSessionContextTimeToLiveMinutes(): int
    {
        return (int) \Craft::parseEnv($this->sessionContextTimeToLiveMinutes);
    }

    public function getSessionContextCount(): int
    {
        return (int) \Craft::parseEnv($this->sessionContextCount);
    }

    public function getSessionContextSecret(): string
    {
        return \Craft::parseEnv($this->sessionContextSecret);
    }

    public function getSessionContextHumanReadable(): string
    {
        switch ($this->sessionContext) {
            case self::CONTEXT_TYPE_SESSION:
                return 'PHP Session';

            case self::CONTEXT_TYPE_DATABASE:
                return 'Database Table';

            case self::CONTEXT_TYPE_PAYLOAD:
            default:
                return 'Encrypted Payload';
        }
    }

    /**
     * Takes a comma or newline (or both) separated string
     * and returns a cleaned up, unique value array.
     */
    private function getArrayFromDelimitedText(?string $value = null): array
    {
        if (empty($value)) {
            return [];
        }

        $array = preg_split('/\s+(?=([^"]*"[^"]*")*[^"]*$)|\n|,/', $value);
        $array = array_map('trim', $array);
        $array = array_map(
            function ($value) {
                return trim($value, '"');
            },
            $array
        );
        $array = array_filter($array);
        $array = array_unique($array);

        return array_filter($array);
    }

    /**
     * @param string $path
     */
    private function getAbsolutePath($path): string
    {
        $isAbsolute = $this->isFolderAbsolute($path);

        return $isAbsolute ? $path : (\Craft::$app->path->getSiteTemplatesPath().'/'.$path);
    }

    /**
     * @param string $path
     */
    private function isFolderAbsolute($path): bool
    {
        return preg_match('/^(?:\/|\\\|\w\:\\\).*$/', $path);
    }

    private function getTemplatesInDirectory(?string $templateDirectoryPath = null): array
    {
        if ('/' === $templateDirectoryPath || !file_exists($templateDirectoryPath) || !is_dir($templateDirectoryPath)) {
            return [];
        }

        $fs = new Finder();

        /** @var SplFileInfo[] $fileIterator */
        $fileIterator = $fs
            ->in($templateDirectoryPath)
            ->name('*.html')
            ->name('*.twig')
            ->files()
        ;

        $files = [];

        try {
            foreach ($fileIterator as $file) {
                $path = $file->getRealPath();
                $files[$path] = pathinfo($path, \PATHINFO_BASENAME);
            }
        } catch (\RuntimeException $e) {
            return [];
        }

        return $files;
    }
}
