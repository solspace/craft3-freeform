<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class CaptchaInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformCaptchaInputType';
    }

    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        $fieldDefinitions = [
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The Captcha field input name.',
            ],
            'value' => [
                'name' => 'value',
                'type' => Type::string(),
                'description' => 'The Captcha field input value.',
            ],
        ];

        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            $fieldDefinitions,
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
