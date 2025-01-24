<?php

namespace Solspace\Freeform\Services;

use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\Diagnostics\DiagnosticItem;
use Solspace\Freeform\Library\DataObjects\Diagnostics\Validators\NoticeValidator;
use Solspace\Freeform\Library\DataObjects\Diagnostics\Validators\SuggestionValidator;
use Solspace\Freeform\Library\DataObjects\Diagnostics\Validators\WarningValidator;
use Solspace\Freeform\Library\DataObjects\Summary\InstallSummary;
use Solspace\Freeform\Models\Settings;

class DiagnosticsService extends BaseService
{
    public function getServerChecks()
    {
        $trueOrFalse = function ($value) { return (bool) $value; };
        $system = $this->getSummary()->statistics->system;

        return [
            new DiagnosticItem(
                'Craft {{ value.edition == "pro" ? "Pro " }}{{ value.version }}',
                [
                    'version' => $system->craftVersion,
                    'edition' => $system->craftEdition,
                ],
                [
                    new WarningValidator(
                        fn ($value) => version_compare($value['version'], '4.0.0', '>='),
                        'Craft compatibility issue',
                        'You have an incompatible version of Craft installed. This version of Freeform currently supports Craft 4.0.0 and greater.'
                    ),
                    new SuggestionValidator(
                        fn ($value) => version_compare($value['version'], '4.15.0', '<'),
                        'Potential Craft Compatibility issue',
                        "The current version of Freeform installed may not be fully compatible with the version of Craft installed. Please confirm you're using a version of Freeform tested for compatibility with this version of Craft."
                    ),
                ]
            ),
            new DiagnosticItem(
                'PHP {{ value }}',
                $system->phpVersion,
                [
                    new WarningValidator(
                        fn ($value) => version_compare($value, '8.0.2', '>='),
                        'PHP Compatibility issue',
                        'You have an incompatible version of PHP installed for this site environment. This version of Freeform currently supports PHP 8.0.2 and greater.'
                    ),
                    new SuggestionValidator(
                        fn ($value) => version_compare($value, '8.3', '<'),
                        'Potential PHP Compatibility issue',
                        "The current version of Freeform installed may not be fully compatible with the version of PHP installed for this site environment. Please confirm you're using a version of Freeform tested for compatibility with this version of PHP."
                    ),
                ]
            ),
            new DiagnosticItem(
                '{{ value.driver == "pgsql" ? "PostgreSQL" : "MySQL" }} {{ value.version }}',
                [
                    'driver' => $system->databaseDriver,
                    'version' => \Craft::$app->db->getServerVersion(),
                ],
                [
                    new WarningValidator(
                        function ($value) {
                            if ('mysql' !== $value['driver']) {
                                return true;
                            }

                            return version_compare($value['version'], '5.5', '>');
                        },
                        'MySQL Compatibility issue',
                        'You have an incompatible version of MySQL installed for this site environment. The current minimum MySQL version Freeform supports is 5.5.x and greater.'
                    ),
                    new WarningValidator(
                        function ($value) {
                            if ('pgsql' !== $value['driver']) {
                                return true;
                            }

                            return version_compare($value['version'], '9.5', '>');
                        },
                        'PostgreSQL Compatibility issue',
                        'You have an incompatible version of PostgreSQL installed for this site environment. The current minimum PostgreSQL version Freeform supports is 9.5.x and greater.'
                    ),
                ]
            ),
            new DiagnosticItem(
                'Memory Limit: [color]{{ value }}[/color]',
                \ini_get('memory_limit'),
                [
                    new WarningValidator(
                        function ($value) {
                            preg_match('/^(-?\d+)(\w)?/', $value, $matches);
                            $number = (int) ($matches[1] ?? -1);
                            $measurement = isset($matches[2]) ? strtolower($matches[2]) : null;

                            $multiplier = 1;

                            switch ($measurement) {
                                case 'k':
                                    $multiplier = 1024;

                                    break;

                                case 'm':
                                    $multiplier = 1024 ** 2;

                                    break;

                                case 'g':
                                    $multiplier = 1024 ** 3;

                                    break;
                            }

                            $bytes = $number * $multiplier;
                            $min = 128 * (1024 ** 2);

                            return -1 === $bytes || $bytes >= $min;
                        },
                        'Memory Limit issue',
                        'Craft and Freeform recommend a minimum memory limit of 256M. Please consider increasing the memory limit for this server environment to avoid any potential issues.'
                    ),
                ]
            ),
            new DiagnosticItem(
                'PHP Sessions: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                \PHP_SESSION_ACTIVE === session_status() && isset($_SESSION) && session_id(),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Potential issue with PHP Sessions',
                        'We attempted to test your environment for a valid PHP session and it failed. It’s possible either your environment does not have them enabled or you have an invalid path set to the PHP Sessions directory.'
                    ),
                ]
            ),
            new DiagnosticItem(
                'BC Math extension: [color]{{ value ? "Enabled" : "Not Found" }}[/color]',
                \extension_loaded('bcmath'),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Missing BC Math PHP extension',
                        'Some parts of Freeform depend on having the BC Math extension enabled for your environment. Please have one of them enabled to avoid potential issues with Freeform.'
                    ),
                ]
            ),
            new DiagnosticItem(
                'ImageMagick extension: [color]{{ value ? "Enabled" : "Not Found" }}[/color]',
                \extension_loaded('imagick') || \extension_loaded('gd'),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Missing GD extension or ImageMagick extension',
                        'Some parts of Freeform depend on having either the GD extension or ImageMagick extension enabled for your environment. Please have one of them enabled to avoid potential issues with Freeform.'
                    ),
                ]
            ),
        ];
    }

    public function getFreeformStats()
    {
        $freeform = Freeform::getInstance();
        $statistics = $this->getSummary()->statistics;

        $formTemplates = $freeform->settings->getCustomFormTemplates();
        $emailTemplates = $freeform->notifications->getAllNotifications();

        $integrations = $freeform->integrations->getAllIntegrations();
        $webhooks = $freeform->webhooks->getAll();

        $isSpamFolderEnabled = $freeform->settings->isSpamFolderEnabled();

        $integrationList = [];
        foreach ($integrations as $integration) {
            $integrationList[] = $integration->name;
        }

        foreach ($webhooks as $webhook) {
            $integrationList[] = $webhook->name;
        }

        $formTypes = [];
        foreach ($freeform->formTypes->getTypes(false) as $formType) {
            $formTypes[] = $formType['name'];
        }

        return [
            new DiagnosticItem(
                '<b>{{ value }}</b> Forms',
                $statistics->totals->forms
            ),
            new DiagnosticItem(
                '<b>{{ value }}</b> Fields',
                $statistics->totals->fields
            ),
            new DiagnosticItem(
                '<b>{{ value }}</b> Submissions',
                $statistics->totals->submissions
            ),
            new DiagnosticItem(
                $isSpamFolderEnabled
                    ? '<b>{{ value }}</b> Submissions marked as Spam'
                    : '<b>{{ value }}</b> Submissions blocked as Spam',
                $statistics->totals->spam
            ),
            new DiagnosticItem(
                '<b>{{ value }}</b> Formatting templates',
                \count($formTemplates)
            ),
            new DiagnosticItem(
                '<b>{{ value }}</b> Email Notification templates',
                \count($emailTemplates)
            ),
            new DiagnosticItem(
                '<b>{{ value|length }}</b> API integrations{{ value|length ? ": " }}{{ value|join(", ") }}',
                $integrationList
            ),
            new DiagnosticItem(
                '<b>{{ value|length }}</b> Additional form types{{ value|length ? ": " }}{{ value|join(", ") }}',
                $formTypes
            ),
        ];
    }

    public function getFreeformChecks()
    {
        [$emailTransport, $emailIssues] = $this->getEmailSettings();

        return [
            new DiagnosticItem(
                'Freeform {{ value.isPro ? "Pro " }}{{ value.version }}',
                [
                    'isPro' => Freeform::getInstance()->isPro(),
                    'version' => Freeform::getInstance()->getVersion(),
                ],
                [
                    new SuggestionValidator(
                        fn ($value) => version_compare($value['version'], '5.0.0', '>='),
                        'A new major version of Freeform is available!',
                        '<a href="{{ extra.url }}">Update to Freeform 5</a> to unlock an impressive new form-building experience, flexible templating options and much more! Freeform 5 is compatible with both Craft 4 and Craft 5.',
                        ['url' => 'https://docs.solspace.com/craft/freeform/v5/']
                    ),
                ]
            ),
            new DiagnosticItem(
                'Craft Email configuration: <b>{{ value.transport }}</b>',
                ['transport' => $emailTransport, 'issues' => $emailIssues]
            ),
            'Spam settings' => [
                new DiagnosticItem(
                    'Honeypot: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSummary()->statistics->spam->honeypot
                ),
                new DiagnosticItem(
                    'Honeypot JS enhancement: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSummary()->statistics->spam->javascriptEnhancement,
                    [
                        new NoticeValidator(
                            fn ($value) => !$value,
                            '',
                            'This being enabled could potentially be problematic with caching, etc. If doing so, please be sure to manually refresh this token.'
                        ),
                    ],
                    fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Spam Folder: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSummary()->statistics->spam->spamFolder,
                    [
                        new SuggestionValidator(
                            fn ($value) => $value,
                            'Enable built-in Spam Folder',
                            'Freeform includes a built-in Spam Folder. It is beneficial for most sites to have this enabled to catch false positives due to spam configuration issues or rare cases. Freeform will let through spammy submissions but flag them as spam instead of blocking them outright. The benefit is that you can see false positives and learn why they were flagged as spam. You can also recover these and trigger proper email notifications, etc. <a href="{{ extra.url }}">Enable Spam Folder ></a>',
                            ['url' => UrlHelper::cpUrl('freeform/settings/spam')]
                        ),
                        new NoticeValidator(
                            fn ($value) => $value,
                            '',
                            'It is beneficial for most sites to have this enabled to catch false positives and/or look for patterns with spam.'
                        ),
                    ],
                    fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Spam Blocking: [color]{{ value|join(", ") }}[/color]',
                    $this->getSpamBlockers(),
                    [],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Captcha Service: [color]{{ value.enabled ? value.type : "Disabled" }}[/color]',
                    [
                        'enabled' => $this->getSummary()->statistics->spam->recaptcha,
                        'type' => $this->getRecaptchaType(),
                    ],
                    [],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
            ],
            'General Settings' => [
                new DiagnosticItem(
                    'Disable Submit Button on Form Submit: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSummary()->statistics->settings->disableSubmit,
                    [],
                    fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Automatically Scroll to Form on Errors and Multipage forms: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSummary()->statistics->settings->autoScroll,
                    [],
                    fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Freeform Script Insertion Location: [color]{{ value|capitalize }}[/color]',
                    $this->getSummary()->statistics->settings->jsInsertLocation,
                    [
                        new NoticeValidator(
                            fn ($value) => Settings::SCRIPT_INSERT_LOCATION_MANUAL !== $value,
                            '',
                            "Please be sure to manually load Freeform's JS and CSS with the 'freeform.loadFreeformPlugin()' function in your template(s)."
                        ),
                    ],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Freeform Script Insert Type: [color]{{ value }}[/color]',
                    $this->getJsInsertType(),
                    [],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Freeform Session Context: [color]{{ value }}[/color]',
                    $this->getSettingsService()->getSettingsModel()->getSessionContextHumanReadable(),
                    [],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Enable Search Index Updating on New Submissions: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                    $this->getSettingsService()->getSettingsModel()->updateSearchIndexes,
                    [],
                    fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
                ),
                new DiagnosticItem(
                    'Automatically Purge Submission Data: [color]{{ value.enabled ? "Enabled, "~value.interval~" days" : "Disabled"  }}[/color]',
                    [
                        'enabled' => $this->getSummary()->statistics->settings->purgeSubmissions,
                        'interval' => $this->getSummary()->statistics->settings->purgeInterval,
                    ],
                    [],
                    fn () => DiagnosticItem::COLOR_BASE
                ),
            ],
            new DiagnosticItem(
                'Formatting Templates Directory Path: [color]{{ value ? value : "Not set" }}[/color]',
                $this->getSettingsService()->getSettingsModel()->formTemplateDirectory,
                [
                    new NoticeValidator(
                        function ($value) {
                            if ($value) {
                                if ('/' !== substr($value, 0, 1)) {
                                    $value = \Craft::getAlias('@templates').\DIRECTORY_SEPARATOR.$value;
                                }

                                return file_exists($value) && is_dir($value);
                            }

                            return true;
                        },
                        '',
                        'Formatting Templates Directory Path: Not set correctly'
                    ),
                ],
                fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Email Template Storage Type: [color]{{ value }}[/color]',
                $this->getSettingsService()->getSettingsModel()->getEmailStorageTypeName(),
                [],
                fn () => DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Email Templates Directory Path: [color]{{ value ? value : "Not set" }}[/color]',
                $this->getSettingsService()->getSettingsModel()->emailTemplateDirectory,
                [
                    new NoticeValidator(
                        function ($value) {
                            if ($value) {
                                if ('/' !== substr($value, 0, 1)) {
                                    $value = \Craft::getAlias('@templates').\DIRECTORY_SEPARATOR.$value;
                                }

                                return file_exists($value) && is_dir($value);
                            }

                            return true;
                        },
                        '',
                        'Email Notification Templates Directory Path: Not set correctly'
                    ),
                ],
                fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Success Templates Directory Path: [color]{{ value ? value : "Not set" }}[/color]',
                $this->getSettingsService()->getSettingsModel()->successTemplateDirectory,
                [
                    new NoticeValidator(
                        function ($value) {
                            if ($value) {
                                if ('/' !== substr($value, 0, 1)) {
                                    $value = \Craft::getAlias('@templates').\DIRECTORY_SEPARATOR.$value;
                                }

                                return file_exists($value) && is_dir($value);
                            }

                            return true;
                        },
                        '',
                        'Success Templates Directory Path: Not set correctly'
                    ),
                ],
                fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Developer Digest Email: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                \count($this->getSettingsService()->getDigestRecipients()) > 0,
                [
                    new SuggestionValidator(
                        fn ($value) => $value,
                        'Enable the Developer Digest feature',
                        "The Developer Digest sends weekly or daily emails on the day specified to any email address(es) you specify. This will include a snapshot of the previous period's performance and any logged errors and upgrade notices. This is very beneficial for keeping your finger on the pulse of this website."
                    ),
                ],
                fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Update Warnings & Notices: [color]{{ value ? "Enabled" : "Disabled" }}[/color]',
                (bool) $this->getSettingsService()->getSettingsModel()->displayFeed,
                [
                    new SuggestionValidator(
                        fn ($value) => $value,
                        'Enable the Update Warnings & Notices feature',
                        'Freeform will detect if any important updates, notices or warnings are available for this site specifically, and display them on the dashboard. Examples of this might be expiring API integrations and fixes for bugs that likely affect your current site. We respect your privacy, and this information cannot and never will make it to Solspace.com servers. The checks only happen locally here on your site after automatically downloading a generic JSON file from Solspace.com.'
                    ),
                ],
                fn ($value) => $value ? DiagnosticItem::COLOR_PASS : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                'Errors logged: [color]{{ value ? value~" errors found" : "None found" }}[/color]',
                Freeform::getInstance()->logger->getLogReader()->count(),
                [
                    new WarningValidator(
                        function ($value) {
                            return !$value;
                        },
                        '{{ extra.count }} Errors logged in the Freeform Error Log',
                        "Please check out the Freeform error log to see the issues logged. These could potentially be harmless notices or issues that are preventing Freeform from working correctly. Also take note of the dates for each, as it's possible they may just be old errors that are no longer an issue. <a href=\"{{ extra.url }}\">View Freeform error log ></a>",
                        [
                            'url' => UrlHelper::cpUrl('freeform/settings/error-log'),
                            'count' => Freeform::getInstance()->logger->getLogReader()->count(),
                        ]
                    ),
                ],
                fn ($value) => $value > 0 ? DiagnosticItem::COLOR_ERROR : DiagnosticItem::COLOR_BASE
            ),
            new DiagnosticItem(
                null,
                Freeform::getInstance()->forms->isPossibleLoadingStaticScripts(),
                [
                    new WarningValidator(
                        fn ($value) => $value,
                        'Freeform Script Insert Check Failed',
                        'It appears that your server has rewrite rules checking if URLs are actual files, which can cause issues with Freeform\'s script loading in the front end and forms not working correctly. Please change the "Freeform Script Insert Type" setting to "As Files" instead.',
                    ),
                ],
            ),
        ];
    }

    private function getEmailSettings(): array
    {
        $from = App::mailSettings()->fromEmail;

        $issues = null;

        switch (App::mailSettings()->transportType) {
            case Smtp::class:
                $transport = 'SMTP';

                $notifications = Freeform::getInstance()->notifications->getAllNotifications();
                foreach ($notifications as $notification) {
                    if ($from !== $notification->getFromEmail()) {
                        $issues = 'misaligned_from';
                    }
                }

                break;

            case Gmail::class:
                $transport = 'Gmail';

                break;

            case Sendmail::class:
                $transport = 'Sendmail';

                break;

            default:
                $transport = 'None';
        }

        return [$transport, $issues];
    }

    private function getSpamBlockers(): array
    {
        $spam = $this->getSummary()->statistics->spam;

        $blockers = [];
        if ($spam->blockEmail) {
            $blockers[] = Freeform::t('Email');
        }

        if ($spam->blockKeywords) {
            $blockers[] = Freeform::t('Keywords');
        }

        if ($spam->blockIp) {
            $blockers[] = Freeform::t('IP addresses');
        }

        if ($spam->minSubmitTime) {
            $blockers[] = Freeform::t('Minimum Submit Time');
        }

        if ($spam->submitExpiration) {
            $blockers[] = Freeform::t('Submit Expiration');
        }

        if ($spam->submissionThrottling) {
            $blockers[] = Freeform::t('Submission Throttling');
        }

        if (empty($blockers)) {
            $blockers[] = Freeform::t('Disabled');
        }

        return $blockers;
    }

    private function getRecaptchaType(): ?string
    {
        return match ($this->getSummary()->statistics->spam->recaptchaType) {
            Settings::RECAPTCHA_TYPE_V2_CHECKBOX => 'reCAPTCHA V2 Checkbox',
            Settings::RECAPTCHA_TYPE_V2_INVISIBLE => 'reCAPTCHA V2 Invisible',
            Settings::RECAPTCHA_TYPE_V3 => 'reCAPTCHA V3',
            Settings::RECAPTCHA_TYPE_H_CHECKBOX => 'hCaptcha Checkbox',
            Settings::RECAPTCHA_TYPE_H_INVISIBLE => 'hCaptcha Invisible',
            default => null,
        };
    }

    private function getJsInsertType(): string
    {
        return match ($this->getSummary()->statistics->settings->jsInsertType) {
            Settings::SCRIPT_INSERT_TYPE_POINTERS => 'As Static URLs',
            Settings::SCRIPT_INSERT_TYPE_FILES => 'As Files',
            Settings::SCRIPT_INSERT_TYPE_INLINE => 'Inline Scripts',
            default => '',
        };
    }

    private function getSummary(): InstallSummary
    {
        static $summary;
        if (null === $summary) {
            $summary = Freeform::getInstance()->summary->getSummary();
        }

        return $summary;
    }
}
