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

namespace Solspace\Freeform\Library\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    private static array $instance = [];

    public static function getOrCreateFileLogger(string $category, string $logfilePath): LoggerInterface
    {
        $hash = sha1($category.$logfilePath);

        if (!isset(self::$instance[$hash])) {
            $logger = new Logger($category);
            $logger->pushHandler(new StreamHandler($logfilePath, Logger::DEBUG));

            self::$instance[$hash] = $logger;
        }

        return self::$instance[$hash];
    }
}
