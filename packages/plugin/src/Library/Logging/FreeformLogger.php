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

namespace Solspace\Freeform\Library\Logging;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

class FreeformLogger
{
    public const LEVEL_DEBUG = Logger::DEBUG;
    public const LEVEL_INFO = Logger::INFO;
    public const LEVEL_NOTICE = Logger::NOTICE;
    public const LEVEL_WARNING = Logger::WARNING;
    public const LEVEL_ERROR = Logger::ERROR;
    public const LEVEL_CRITICAL = Logger::CRITICAL;
    public const LEVEL_ALERT = Logger::ALERT;
    public const LEVEL_EMERGENCY = Logger::EMERGENCY;

    public const FREEFORM = 'Freeform';

    // NEW
    public const INTEGRATION = 'Integration';
    public const EMAIL_MARKETING_INTEGRATION = 'Email Marketing Integration';
    public const CRM_INTEGRATION = 'CRM Integration';
    public const ELEMENTS_INTEGRATION = 'Elements Integration';
    public const CAPTCHAS_INTEGRATION = 'Captchas Integration';
    public const WEBHOOKS_INTEGRATION = 'Webhooks Integration';

    // OLD
    public const FORM = 'Form';
    public const ADMIN_NOTIFICATION = 'Admin Notification';
    public const CONDITIONAL_NOTIFICATION = 'Conditional Notification';
    public const EMAIL_NOTIFICATION = 'Email Notification';
    public const STRIPE = 'Stripe';
    public const DASHBOARD = 'Dashboard';
    public const MAILER = 'Mailer service';
    public const PAYMENT_GATEWAY = 'Payment Gateway';
    public const CONDITIONAL_RULE = 'Conditional Rule';
    public const ELEMENT_CONNECTION = 'Element Connection';
    public const PAYLOAD_FORWARDING = 'POST Forwarding';
    public const FEATURE_BUNDLES = 'bundles';

    private static array $colorMap = [
        self::FREEFORM => '#333333',
        // NEW
        self::INTEGRATION => '#333333',
        self::EMAIL_MARKETING_INTEGRATION => '#333333',
        self::CRM_INTEGRATION => '#333333',
        self::ELEMENTS_INTEGRATION => '#333333',
        self::CAPTCHAS_INTEGRATION => '#333333',
        self::WEBHOOKS_INTEGRATION => '#333333',
        // OLD
        self::FORM => '#333333',
        self::ADMIN_NOTIFICATION => '#333333',
        self::CONDITIONAL_NOTIFICATION => '#333333',
        self::EMAIL_NOTIFICATION => '#333333',
        self::STRIPE => '#333333',
        self::DASHBOARD => 'red',
        self::MAILER => '#333333',
        self::PAYMENT_GATEWAY => '#333333',
        self::CONDITIONAL_RULE => '#333333',
        self::ELEMENT_CONNECTION => '#333333',
        self::PAYLOAD_FORWARDING => '#333333',
        self::FEATURE_BUNDLES => '#333333',
    ];

    private static array $levelColorMap = [
        'DEBUG' => '#6c757d',
        'INFO' => '#0284c7',
        'NOTICE' => '#28a745',
        'WARNING' => '#ffc107',
        'ERROR' => '#dc3545',
        'CRITICAL' => '#dc3545',
        'ALERT' => '#dc3545',
        'EMERGENCY' => '#dc3545',
    ];

    private static array $loggers = [];

    public static function getInstance(string $category, ?string $fileName = null, ?int $level = null): LoggerInterface
    {
        if (!isset(self::$loggers[$category])) {
            self::$loggers[$category] = LoggerFactory::getOrCreateFileLogger($category, self::getLogfilePath($fileName), $level);
        }

        return self::$loggers[$category];
    }

    public static function getLogfilePath(?string $fileName): string
    {
        return \Craft::$app->path->getLogPath().'/'.($fileName ?? 'freeform.log');
    }

    public static function getColor(string $level): string
    {
        return self::$levelColorMap[$level] ?? '#000000';
    }
}
