<?php

namespace Solspace\Freeform\Services;

use craft\base\PluginInterface;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationTypeProvider;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationLoggerProvider;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Fields\StripeField;
use Solspace\Freeform\Library\DataObjects\Diagnostics\DiagnosticItem;
use Solspace\Freeform\Library\DataObjects\Diagnostics\Validators\SuggestionValidator;
use Solspace\Freeform\Library\DataObjects\Diagnostics\Validators\WarningValidator;
use Solspace\Freeform\Library\DataObjects\Summary\InstallSummary;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\Form\FormFieldRecord;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\IntegrationRecord;

class DiagnosticsService extends BaseService
{
    public function __construct(
        $config,
        private IntegrationTypeProvider $integrationTypeProvider,
    ) {
        parent::__construct($config);
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getServerChecks(): array
    {
        $trueOrFalse = function ($value) { return (bool) $value; };
        $system = $this->getSummary()->statistics->system;
        $minCraftVersion = '4.0.0';
        $maxCraftVersion = '5.7.0';
        $minPhpVersion = '8.0.2';
        $maxPhpVersion = '8.4.0';

        return [
            new DiagnosticItem(
                '<span class="diag-check diag-enabled"></span>Freeform <b>{{ value.edition|title }} {{ value.version }}</b>',
                [
                    'edition' => Freeform::getInstance()->edition,
                    'version' => Freeform::getInstance()->getVersion(),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ (value.version <= "'.$maxCraftVersion.'" and value.version >= "'.$minCraftVersion.'") ? "enabled" : (value.version >= "'.$maxCraftVersion.'" ? "info" : "warning") }}"></span>Craft <b>{{ value.edition|title }} {{ value.version }}</b>',
                [
                    'version' => $system->craftVersion,
                    'edition' => $system->craftEdition,
                ],
                [
                    new WarningValidator(
                        fn ($value) => version_compare($value['version'], $minCraftVersion, '>='),
                        'Craft compatibility issue',
                        'The current minimum Craft version Freeform supports is '.$minCraftVersion.' or greater.'
                    ),
                    new SuggestionValidator(
                        fn ($value) => version_compare($value['version'], $maxCraftVersion, '<'),
                        'Potential Craft Compatibility issue',
                        'This version of Freeform may not be fully compatible with this version of Craft and may encounter issues. Please check if there are any updates available.'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ (value < "'.$maxPhpVersion.'" and value > "'.$minPhpVersion.'") ? "enabled" : (value > "'.$minPhpVersion.'" ? "info" : "warning") }}"></span>PHP <b>{{ value }}</b>',
                $system->phpVersion,
                [
                    new WarningValidator(
                        fn ($value) => version_compare($value, $minPhpVersion, '>='),
                        'PHP Compatibility issue',
                        'The current minimum PHP version Freeform supports is '.$minPhpVersion.' or greater.'
                    ),
                    new SuggestionValidator(
                        fn ($value) => version_compare($value, $maxPhpVersion, '<='),
                        'Potential PHP Compatibility issue',
                        'This version of Freeform may not be fully compatible with this version of PHP and may encounter issues. Please check if there are any updates available.'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value.version > "5.5" ? "enabled" : "warning" }}"></span><span class="item-inline">'.Freeform::t('Database Driver').': <b>{{ value.driver == "pgsql" ? "PostgreSQL" : value.driver == "mysql" ? "MySQL" : "MariaDB" }} {{ value.version }}</b></span>',
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
                        'The current minimum MySQL version Freeform supports is 5.5.x or greater.'
                    ),
                    new WarningValidator(
                        function ($value) {
                            if ('pgsql' !== $value['driver']) {
                                return true;
                            }

                            return version_compare($value['version'], '9.5', '>');
                        },
                        'PostgreSQL Compatibility issue',
                        'The current minimum PostgreSQL version Freeform supports is 9.5.x or greater.'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-spacer"></span>'.Freeform::t('OS').': <b>{{ value }}</b>',
                \sprintf('%s %s', \PHP_OS, php_uname('r')),
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-spacer"></span>'.Freeform::t('Memory Limit').': <b>{{ value }}</b>',
                \ini_get('memory_limit'),
                [
                    // Suggestion validator for memory limits between 256M and 511M
                    new SuggestionValidator(
                        function ($value) {
                            preg_match('/^(-?\d+)(\w)?/', $value, $matches);
                            $number = (int) ($matches[1] ?? -1);
                            $measurement = isset($matches[2]) ? strtolower($matches[2]) : null;

                            $multiplier = match ($measurement) {
                                'k' => 1024,
                                'm' => 1024 ** 2,
                                'g' => 1024 ** 3,
                                default => 1,
                            };

                            $bytes = $number * $multiplier;
                            $min256M = 256 * 1024 ** 2; // 256M in bytes
                            $max511M = 511 * 1024 ** 2; // 511M in bytes

                            // Trigger the suggestion if the memory limit is outside the range of 256M to 511M
                            return $bytes < $min256M || $bytes >= $max511M;
                        },
                        'Memory Limit suggestion',
                        'Freeform recommends a memory limit of 512M or greater. Please consider increasing the memory limit.'
                    ),
                    // Warning validator for memory limits below 256M
                    new WarningValidator(
                        function ($value) {
                            preg_match('/^(-?\d+)(\w)?/', $value, $matches);
                            $number = (int) ($matches[1] ?? -1);
                            $measurement = isset($matches[2]) ? strtolower($matches[2]) : null;

                            $multiplier = match ($measurement) {
                                'k' => 1024,
                                'm' => 1024 ** 2,
                                'g' => 1024 ** 3,
                                default => 1,
                            };

                            $bytes = $number * $multiplier;
                            $min = 256 * (1024 ** 2); // 256M in bytes

                            // Trigger the warning if the memory limit is less than 256M
                            return -1 === $bytes || $bytes >= $min;
                        },
                        'Memory Limit issue',
                        'Freeform requires a minimum memory limit of 256M but recommends using at least 512M. Please consider increasing the memory limit.'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "warning" }}"></span>'.Freeform::t('PHP Sessions'),
                \PHP_SESSION_ACTIVE === session_status() && isset($_SESSION) && session_id(),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Potential issue with PHP Sessions',
                        'Tested server environment for a valid PHP session and it failed.'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "warning" }}"></span>'.Freeform::t('BC Math extension'),
                \extension_loaded('bcmath'),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Missing BC Math PHP extension',
                        'Missing BC Math PHP extension'
                    ),
                ]
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "warning" }}"></span>'.Freeform::t('ImageMagick extension'),
                \extension_loaded('imagick') || \extension_loaded('gd'),
                [
                    new WarningValidator(
                        $trueOrFalse,
                        'Missing GD extension or ImageMagick extension',
                        'Missing GD extension or ImageMagick extension'
                    ),
                ]
            ),
        ];
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getSiteChecks(): array
    {
        $trueOrFalse = function ($value) { return (bool) $value; };
        $system = $this->getSummary()->statistics->system;
        [$emailTransport, $emailIssues] = $this->getEmailSettings();

        return [
            new DiagnosticItem(
                '<span class="diag-check diag-spacer"></span>'.Freeform::t('Environment').': <code>{{ value }}</code>',
                \Craft::$app->getConfig()->env,
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Dev Mode'),
                \Craft::$app->getConfig()->getGeneral()->devMode,
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Allow Admin Changes'),
                \Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Async CSRF Inputs'),
                \Craft::$app->getConfig()->getGeneral()->asyncCsrfInputs,
            ),
            new DiagnosticItem(
                '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Craft Email configuration').': <b>{{ value.transport }}</b></span>',
                ['transport' => $emailTransport, 'issues' => $emailIssues],
            ),
        ];
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getFreeformStats(): array
    {
        $freeform = Freeform::getInstance();
        $statistics = $this->getSummary()->statistics;

        $formTemplates = $freeform->settings->getCustomFormTemplates();
        $successTemplates = $freeform->settings->getSuccessTemplates();
        $emailTemplates = $freeform->notifications->getAllNotifications();
        $formTypes = $freeform->formTypes->getTypes();
        $integrations = $freeform->integrations->getAllIntegrations();

        $diagnosticItems = [
            new DiagnosticItem(
                Freeform::t('Forms').': <b>{{ value }}</b>',
                $statistics->totals->forms
            ),
            new DiagnosticItem(
                Freeform::t('Fields').': <b>{{ value }}</b>',
                $statistics->totals->fields
            ),
            new DiagnosticItem(
                Freeform::t('Favorites Fields').': <b>{{ value }}</b>',
                $statistics->totals->favoriteFields
            ),
            new DiagnosticItem(
                Freeform::t('Submissions').': <b>{{ value }}</b>',
                $statistics->totals->submissions
            ),
            new DiagnosticItem(
                Freeform::t('Spam Submissions').': <b>{{ value }}</b>',
                $statistics->totals->spam
            ),
            new DiagnosticItem(
                Freeform::t('Formatting Templates').': <b>{{ value }}</b>',
                \count($formTemplates)
            ),
            new DiagnosticItem(
                Freeform::t('Email Notification Templates').': <b>{{ value }}</b>',
                \count($emailTemplates)
            ),
            new DiagnosticItem(
                Freeform::t('Success Templates').': <b>{{ value }}</b>',
                \count($successTemplates)
            ),
            new DiagnosticItem(
                Freeform::t('Integrations').': <b>{{ value }}</b>',
                \count($integrations)
            ),
        ];

        // Check if Freeform Pro is enabled, then add the Form types item
        if ($freeform->isPro()) {
            $diagnosticItems[] = new DiagnosticItem(
                Freeform::t('Form Types').': <b>{{ value|length }}</b>',
                \count($formTypes)
            );
        }

        return $diagnosticItems;
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getFreeformIntegrations(): array
    {
        $integrations = $this->getIntegrationCount();
        $diagnosticItems = [];

        foreach ($integrations as $integration) {
            $name = $integration['name'];
            $version = $integration['version'];
            $count = $integration['count'];

            // Mapping versions to their display names
            $versionMap = [
                'checkbox' => 'Checkbox',
                'invisible' => 'Invisible',
                'v2-checkbox' => 'v2 Checkbox',
                'v2-invisible' => 'v2 Invisible',
            ];

            // Modify version text based on specific conditions or use defaults
            $version = $versionMap[strtolower($version)] ?? $version;
            $label = "{$name}".($version ? " ({$version}): " : ': ')."<b>{$count}</b> ".Freeform::t(1 === $count ? 'form' : 'forms');

            $diagnosticItems[] = new DiagnosticItem($label, ['value' => $integration]);
        }

        return $diagnosticItems;
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getFreeformFormType(): array
    {
        $freeform = Freeform::getInstance();
        $statistics = $this->getSummary()->statistics;

        if ($freeform->isPro()) {
            return [
                new DiagnosticItem(
                    Freeform::t('Regular').': <b>{{ value }}</b> {{ value != 1 ? "'.Freeform::t('forms').'" : "'.Freeform::t('form').'" }}',
                    $statistics->totals->regularForm
                ),
                new DiagnosticItem(
                    Freeform::t('Payments').': <b>{{ value }}</b> {{ value != 1 ? "'.Freeform::t('forms').'" : "'.Freeform::t('form').'" }}',
                    $this->getFormsWithPaymentIntegrations()
                ),
            ];
        }

        return []; // Or any other action for non-Pro users
    }

    /**
     * @return DiagnosticItem[]
     */
    public function getFreeformConfigurations(): array
    {
        [$emailTransport, $emailIssues] = $this->getEmailSettings();

        return [
            Freeform::t('General Settings') => [
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Disable Submit Button on Form Submit'),
                    $this->getSummary()->statistics->settings->disableSubmit
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Automatically Scroll to Form on Errors and Multipage forms'),
                    $this->getSummary()->statistics->settings->autoScroll,
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Script Insert Location').': <b>{{ value }}</b></span>',
                    Freeform::t(
                        match (Freeform::getInstance()->settings->getSettingsModel()->scriptInsertLocation) {
                            Settings::SCRIPT_INSERT_LOCATION_FOOTER => Freeform::t('Page Footer'),
                            Settings::SCRIPT_INSERT_LOCATION_HEADER => Freeform::t('Page Header'),
                            Settings::SCRIPT_INSERT_LOCATION_FORM => Freeform::t('Inside Form'),
                            Settings::SCRIPT_INSERT_LOCATION_MANUAL => Freeform::t('None (add manually)'),
                        }
                    ),
                    [
                        new SuggestionValidator(
                            fn ($value) => Settings::SCRIPT_INSERT_LOCATION_MANUAL !== $value,
                            '',
                            'Please make sure you are adding Freeform’s scripts manually.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Script Insert Type').': <b>{{ value }}</b></span>',
                    $this->getJsInsertType()
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Freeform Session Context').': <b>'.Freeform::t('{{ value }}').'</b></span>',
                    Freeform::t(
                        match (Freeform::getInstance()->settings->getSettingsModel()->sessionContext) {
                            Settings::CONTEXT_TYPE_PAYLOAD => Freeform::t('Encrypted Payload'),
                            Settings::CONTEXT_TYPE_SESSION => Freeform::t('PHP Session'),
                            Settings::CONTEXT_TYPE_DATABASE => Freeform::t('Database'),
                        }
                    ),
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Enable Search Index Updating on New Submissions'),
                    $this->getSettingsService()->getSettingsModel()->updateSearchIndexes
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Use Queue for Email Notifications'),
                    $this->getSettingsService()->getSettingsModel()->useQueueForEmailNotifications
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Use Queue for Integrations'),
                    $this->getSettingsService()->getSettingsModel()->useQueueForIntegrations
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.enabled ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Automatically Purge Submission Data').'{{ value.enabled ? ": <b>"~value.interval~" '.Freeform::t('days').'</b>" : "" }}</span>',
                    [
                        'enabled' => $this->getSummary()->statistics->settings->purgeSubmissions,
                        'interval' => $this->getSummary()->statistics->settings->purgeInterval,
                    ],
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.enabled ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Automatically Purge Unfinalized Assets').'{{ value.enabled ? ": <b>"~value.interval~" '.Freeform::t('hours').'</b>" : "" }}</span>',
                    [
                        'enabled' => $this->getSummary()->statistics->settings->purgeAssets,
                        'interval' => $this->getSummary()->statistics->settings->purgeAssetsInterval / 60,
                    ],
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Site-Aware Forms'),
                    $this->getSettingsService()->getSettingsModel()->sitesEnabled,
                ),
            ],
            Freeform::t('Spam Controls') => [
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Spam Folder'),
                    $this->getSummary()->statistics->spam->spamFolder,
                    [
                        new SuggestionValidator(
                            fn ($value) => $value,
                            '',
                            'Most websites can benefit from using this feature because it helps detect false positives.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Spam Protection Behavior').': <b>{{ value }}</b></span>',
                    Freeform::t(
                        match ($this->getSummary()->statistics->spam->spamProtectionBehavior) {
                            Settings::PROTECTION_DISPLAY_ERRORS => Freeform::t('Display Errors'),
                            Settings::PROTECTION_SIMULATE_SUCCESS => Freeform::t('Simulate Success'),
                            Settings::PROTECTION_RELOAD_FORM => Freeform::t('Reload Form'),
                        }
                    ),
                    [
                        new SuggestionValidator(
                            fn ($value) => 'Display Errors' != $value,
                            '',
                            'We recommend using this solely for debugging during initial site setup, testing adjustments, or troubleshooting Freeform spam issues. Displaying an error can inform spam bots of their failures, leading them to attempt different methods.',
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Bypass All Spam Checks for Logged in Users'),
                    $this->getSummary()->statistics->spam->bypassSpamCheckOnLoggedInUsers
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.enabled ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Minimum Submit Time').'{{ value.enabled ? ":" : "" }} <b>{{ value.enabled ? ""~value.interval~" '.Freeform::t('seconds').'" : "" }}</b></span>',
                    [
                        'enabled' => $this->getSummary()->statistics->spam->minSubmitTime,
                        'interval' => $this->getSummary()->statistics->spam->minSubmitTimeInterval,
                    ],
                    [
                        new WarningValidator(
                            function ($value) {
                                if ('' == $value['interval']) {
                                    return true;
                                }

                                return version_compare($value['interval'], '11', '<');
                            },
                            '',
                            'Setting a value of more than 10 seconds will lead to many false positives for spam. We strongly recommend setting this to a value of no more than 5 seconds.'
                        ),
                        new SuggestionValidator(
                            function ($value) {
                                if ('' == $value['interval']) {
                                    return true;
                                }
                                if ('11' <= $value['interval']) {
                                    return true;
                                }

                                return version_compare($value['interval'], '6', '<');
                            },
                            '',
                            'Setting a value of more than 5 seconds may lead to many false positives for spam.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.enabled ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Form Submit Expiration').'{{ value.enabled ? ":" : "" }} <b>{{ value.enabled ? ""~value.interval~" '.Freeform::t('minutes').'" : "" }}</b></span>',
                    [
                        'enabled' => $this->getSummary()->statistics->spam->submitExpiration,
                        'interval' => $this->getSummary()->statistics->spam->submitExpirationInterval,
                    ],
                    [
                        new WarningValidator(
                            function ($value) {
                                if ('' == $value['interval']) {
                                    return true;
                                }

                                return version_compare($value['interval'], '9', '>');
                            },
                            '',
                            'Setting a value of less than 10 minutes will lead to many false positives for spam. We strongly recommend setting this to a value of no less than 30 minutes.'
                        ),
                        new SuggestionValidator(
                            function ($value) {
                                if ('' == $value['interval']) {
                                    return true;
                                }
                                if ('9' >= $value['interval']) {
                                    return true;
                                }

                                return version_compare($value['interval'], '29', '>');
                            },
                            '',
                            'Setting a value of less than 30 minutes may lead to many false positives for spam.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.count > 0 ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Form Submission Throttling').': <b>{% if value.count != 0 %}{{ value.count }} '.Freeform::t('per').' {{ value.interval == "m" ? "'.Freeform::t('minute').'" : "'.Freeform::t('second').'" }}{% else %}'.Freeform::t('Unlimited').'{% endif %}</b></span>',
                    [
                        'count' => $this->getSummary()->statistics->spam->submissionThrottlingCount,
                        'interval' => $this->getSummary()->statistics->spam->submissionThrottlingTimeFrame,
                    ],
                    [
                        new WarningValidator(
                            fn ($value) => version_compare($value['count'], '0', '<='),
                            '',
                            "This feature is intended for extreme conditions, such as preventing your site from going down if attacked by a spammer. It should NOT be used as a 'fine-tuning' spam measure, as it applies to ALL users. Use extreme caution for larger and more active sites."
                        ),
                    ]
                ),
            ],

            Freeform::t('Template Directories') => [
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Formatting Templates Directory Path').': <code>'.Freeform::t('{{ value ? value : "" }}').'</code></span>',
                    $this->getSettingsService()->getSettingsModel()->formTemplateDirectory,
                    [
                        new WarningValidator(
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
                            'This directory path is not set correctly.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t("Include Freeform's Sample Formatting Templates"),
                    $this->getSettingsService()->getSettingsModel()->defaults->includeSampleTemplates,
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Email Templates Directory Path').': <code>'.Freeform::t('{{ value ? value : "" }}').'</code></span>',
                    $this->getSettingsService()->getSettingsModel()->emailTemplateDirectory,
                    [
                        new WarningValidator(
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
                            'This directory path is not set correctly.'
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Email Template Storage Type').': <b>{{ value }}</b></span>',
                    Freeform::t(
                        match (Freeform::getInstance()->settings->getSettingsModel()->emailTemplateStorageType) {
                            Settings::EMAIL_TEMPLATE_STORAGE_TYPE_FILES => Freeform::t('File'),
                            Settings::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE => Freeform::t('Database'),
                            Settings::EMAIL_TEMPLATE_STORAGE_TYPE_BOTH => Freeform::t('File & Database'),
                        }
                    ),
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Success Templates Directory Path').': <code>'.Freeform::t('{{ value ? value : "" }}').'</code></span>',
                    $this->getSettingsService()->getSettingsModel()->successTemplateDirectory,
                    [
                        new WarningValidator(
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
                            'This directory path is not set correctly.'
                        ),
                    ]
                ),
            ],

            Freeform::t('Notices & Alerts') => [
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Developer Digest Email'),
                    \count($this->getSettingsService()->getDigestRecipients()) > 0
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value ? "enabled" : "disabled" }}"></span>'.Freeform::t('Update Warnings & Notices'),
                    (bool) $this->getSettingsService()->getSettingsModel()->displayFeed
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-spacer"></span><span class="item-inline">'.Freeform::t('Logging Level').': <b>{{ value }}</b></span>',
                    Freeform::t(
                        match (Freeform::getInstance()->settings->getSettingsModel()->loggingLevel) {
                            Settings::LOGGING_LEVEL_INFO => Freeform::t('Info'),
                            Settings::LOGGING_LEVEL_DEBUG => Freeform::t('Debug'),
                            Settings::LOGGING_LEVEL_ERROR => Freeform::t('Errors'),
                        }
                    ),
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.count ? "warning" : "enabled" }}"></span><span class="item-inline">'.Freeform::t('Error Log').': <b>{{ value.count }} '.Freeform::t('item{{ value.count == "1" ? "" : "s" }} found').'</b></span>',
                    [
                        'count' => Freeform::getInstance()->logger->getLogReader()->count(),
                    ],
                    [
                        new WarningValidator(
                            function ($value) {
                                return !$value;
                            },
                            '',
                            Freeform::t('Please check the <a href="{{ extra.url }}">{{ extra.type }}</a> to see if there are any potential issues.'),
                            [
                                'url' => UrlHelper::cpUrl('freeform/settings/error-log'),
                                'count' => Freeform::getInstance()->logger->getLogReader()->count(),
                                'type' => Freeform::t('error log'),
                            ]
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.level != "Error" ? (value.count ? "info" : "enabled") : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Integrations Log').': <b>{{ value.count }} '.Freeform::t('item{{ value.count == "1" ? "" : "s" }} found').'</b></span>',
                    [
                        'count' => Freeform::getInstance()->logger->getLogReader(IntegrationLoggerProvider::LOG_FILE)->count(),
                        'level' => ucfirst($this->getSettingsService()->getSettingsModel()->loggingLevel),
                    ],
                    [
                        new SuggestionValidator(
                            function ($value) {
                                return !$value['count'];
                            },
                            '',
                            Freeform::t('Please check the <a href="{{ extra.url }}">{{ extra.type }}</a> to see if there are any potential issues.'),
                            [
                                'url' => UrlHelper::cpUrl('freeform/settings/integrations-log'),
                                'count' => Freeform::getInstance()->logger->getLogReader()->count(),
                                'type' => Freeform::t('integrations log'),
                            ]
                        ),
                    ]
                ),
                new DiagnosticItem(
                    '<span class="diag-check diag-{{ value.level != "Error" ? (value.count ? "info" : "enabled") : "disabled" }}"></span><span class="item-inline">'.Freeform::t('Email Log').': <b>{{ value.count }} '.Freeform::t('item{{ value.count == "1" ? "" : "s" }} found').'</b></span>',
                    [
                        'count' => Freeform::getInstance()->logger->getLogReader(NotificationLoggerProvider::LOG_FILE)->count(),
                        'level' => ucfirst($this->getSettingsService()->getSettingsModel()->loggingLevel),
                    ],
                    [
                        new SuggestionValidator(
                            function ($value) {
                                return !$value['count'];
                            },
                            '',
                            Freeform::t('Please check the <a href="{{ extra.url }}">{{ extra.type }}</a> to see if there are any potential issues.'),
                            [
                                'url' => UrlHelper::cpUrl('freeform/settings/email-log'),
                                'count' => Freeform::getInstance()->logger->getLogReader()->count(),
                                'type' => Freeform::t('email log'),
                            ]
                        ),
                    ]
                ),
            ],
        ];
    }

    public function getCraftModules(): array
    {
        $diagnosticItems = [];

        foreach (\Craft::$app->getModules() as $module) {
            if ($module instanceof PluginInterface) {
                continue;
            }

            if (!empty($module->id)) {
                $diagnosticItems[] = new DiagnosticItem($module->id.': '.$module::class, []);
            }
        }

        if (empty($diagnosticItems)) {
            $diagnosticItems[] = new DiagnosticItem(Freeform::t('No modules are installed.'), []);
        }

        return $diagnosticItems;
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

    private function getJsInsertType(): string
    {
        return match ($this->getSummary()->statistics->settings->jsInsertType) {
            Settings::SCRIPT_INSERT_TYPE_POINTERS => Freeform::t('Static URLs'),
            Settings::SCRIPT_INSERT_TYPE_FILES => Freeform::t('Asset Bundles'),
            Settings::SCRIPT_INSERT_TYPE_INLINE => Freeform::t('Inline Scripts'),
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

    private function getIntegrationCount(): array
    {
        $integrations = (new Query())
            ->select(['fi.id', 'fi.integrationId', 'fi.formId', 'integrations.class', 'integrations.metadata'])
            ->from(FormIntegrationRecord::TABLE.' fi')
            ->innerJoin(IntegrationRecord::TABLE.' integrations', 'integrations.id = fi.[[integrationId]]')
            ->where(['fi.enabled' => true])
            ->all()
        ;

        $integrationsByForm = [];

        foreach ($integrations as $integration) {
            $id = $integration['integrationId'];

            if (!isset($integrationsByForm[$id])) {
                $type = $this->integrationTypeProvider->getTypeDefinition($integration['class']);

                // Check if the version exists in the type; otherwise, use metadata
                $version = $type->version ?? JsonHelper::decode($integration['metadata'], true)['version'] ?? null;

                $integrationsByForm[$id] = [
                    'name' => $type->name,
                    'version' => $version ?? '', // Provide a default value if version is not found
                    'count' => 0,
                ];
            }

            ++$integrationsByForm[$id]['count'];
        }

        return $integrationsByForm;
    }

    private function getFormsWithPaymentIntegrations(): int
    {
        return FormFieldRecord::find()
            ->select('formId')
            ->distinct()
            ->where(['type' => StripeField::class])
            ->count()
        ;
    }
}
