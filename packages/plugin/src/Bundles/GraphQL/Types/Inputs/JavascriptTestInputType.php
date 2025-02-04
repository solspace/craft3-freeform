<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\JavascriptTest\JavascriptTest;

class JavascriptTestInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformJavascriptTestInputType';
    }

    public static function getType(Form $form): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
        $javascriptTest = $integrationProvider->getSingleton($form, JavascriptTest::class);
        if (!$javascriptTest) {
            return null;
        }

        $inputName = $javascriptTest->getInputName();

        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            [
                $inputName => [
                    'name' => $inputName,
                    'type' => Type::string(),
                    'description' => 'The Javascript Test field input name.',
                ],
            ],
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
