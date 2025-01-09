<?php

namespace Solspace\Freeform\Bundles\Integrations\Providers;

use craft\helpers\StringHelper;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;

class IntegrationLoggerProvider
{
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
                'freeform-integrations.log',
                Logger::DEBUG
            )
        ;
    }
}
