<?php

namespace Solspace\Freeform\Bundles\Integrations\Providers;

use craft\helpers\StringHelper;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Services\SettingsService;

class IntegrationLoggerProvider
{
    public const LOG_FILE = 'freeform-integrations.log';

    private int $level;

    public function __construct(
        SettingsService $settingsService
    ) {
        $this->level = match ($settingsService->getSettingsModel()->loggingLevel) {
            Settings::LOGGING_LEVEL_INFO => Logger::INFO,
            Settings::LOGGING_LEVEL_DEBUG => Logger::DEBUG,
            default => Logger::ERROR,
        };
    }

    public function getLogger(IntegrationInterface|Type $integration): LoggerInterface
    {
        if ($integration instanceof IntegrationInterface) {
            $type = $integration->getTypeDefinition();
        } else {
            $type = $integration;
        }

        $logCategory = StringHelper::toKebabCase($type->getNameWithVersion());

        return Freeform::getInstance()
            ->logger
            ->getLogger(
                $logCategory,
                self::LOG_FILE,
                $this->level
            )
        ;
    }
}
