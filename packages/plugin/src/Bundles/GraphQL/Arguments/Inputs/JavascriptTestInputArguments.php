<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments\Inputs;

use craft\gql\base\Arguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Inputs\JavascriptTestInputType;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\JavascriptTest\JavascriptTest;

class JavascriptTestInputArguments extends Arguments
{
    private static Form $form;

    public static function setForm(Form $form): void
    {
        self::$form = $form;
    }

    public static function getArguments(): array
    {
        $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
        $javascriptTest = $integrationProvider->getSingleton(self::$form, JavascriptTest::class);

        if (!$javascriptTest) {
            return [];
        }

        return [
            'javascriptTest' => [
                'name' => 'javascriptTest',
                'type' => JavascriptTestInputType::getType(),
                'description' => 'The Javascript Test field input name and value.',
            ],
        ];
    }
}
