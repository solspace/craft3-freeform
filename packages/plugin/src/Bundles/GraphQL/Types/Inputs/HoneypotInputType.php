<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use Solspace\Freeform\Bundles\GraphQL\Arguments\HoneypotArguments;

class HoneypotInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformHoneypotInputType';
    }

    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        /*
         * FIXME
         * - Add honeypot field input name (freeform_honeypot_handle or freeform_honeypot_foobar) instead of requiring name / value fields.
         * - Also add proper HoneypotInputArguments so we do not expose query fields for mutations
         */
        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            HoneypotArguments::getArguments(),
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
