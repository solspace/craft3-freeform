<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments\Inputs;

use craft\gql\base\Arguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Inputs\CsrfTokenInputType;

class CsrfTokenInputArguments extends Arguments
{
    public static function getArguments(): array
    {
        $generalConfig = \Craft::$app->getConfig()->getGeneral();
        $csrfTokenName = $generalConfig->csrfTokenName;
        $isCsrfEnabled = $generalConfig->enableCsrfProtection;

        if (!$isCsrfEnabled || !$csrfTokenName) {
            return [];
        }

        return [
            'csrfToken' => [
                'name' => 'csrfToken',
                'type' => CsrfTokenInputType::getType(),
                'description' => 'The CSRF field input name and value.',
            ],
        ];
    }
}
