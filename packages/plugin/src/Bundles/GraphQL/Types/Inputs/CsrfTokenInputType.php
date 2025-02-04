<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use Solspace\Freeform\Bundles\GraphQL\Arguments\CsrfTokenArguments;

class CsrfTokenInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformCsrfTokenInputType';
    }

    /**
     * Users would grab a CSRF token name and value from actions/users/session-info.
     */
    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        /*
         * FIXME
         * - Add CSRF token name field name (CRAFT_CSRF_TOKEN) instead of requiring name / value fields
         * - Also use proper CsrfTokenInputArguments so we do not expose query fields for mutations
         */
        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            CsrfTokenArguments::getArguments(),
            self::getName()
        );

        return GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => function () use ($fields) {
                return $fields;
            },
        ]));
    }
}
