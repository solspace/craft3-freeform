<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Arguments\FileUploadArguments;

class FileUploadInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformFileUploadInputType';
    }

    public static function getType(): ListOfType
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return Type::listOf($inputType);
        }

        // FIXME - Use proper FileUploadInputArguments so we do not expose query fields for mutations
        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            FileUploadArguments::getArguments(),
            self::getName()
        );

        $inputType = GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => function () use ($fields) {
                return $fields;
            },
        ]));

        return Type::listOf($inputType);
    }
}
