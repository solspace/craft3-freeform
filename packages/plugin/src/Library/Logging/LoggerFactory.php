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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Solspace\Freeform\Library\Helpers\CryptoHelper;

class LoggerFactory
{
    private static array $instance = [];

    public static function getOrCreateFileLogger(
        string $category,
        string $logfilePath,
        ?int $level = null
    ): LoggerInterface {
        static $requestId;
        if (null === $requestId) {
            $requestId = CryptoHelper::getUniqueToken();
        }

        $hash = sha1($category.$logfilePath);

        if (!isset(self::$instance[$hash])) {
            $logger = new Logger($category);
            $logger->pushHandler(new StreamHandler($logfilePath, $level ?? Logger::DEBUG));
            $logger->pushProcessor(function ($record) use ($requestId) {
                $record['extra']['requestId'] = $requestId;

                return $record;
            });

            self::$instance[$hash] = $logger;
        }

        return self::$instance[$hash];
    }
}
