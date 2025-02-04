<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects;

use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\AbstractInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects\FormCaptchaGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\FormCaptchaType;

class FormCaptchaInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformFormCaptchaInterface';
    }

    public static function getTypeClass(): string
    {
        return FormCaptchaType::class;
    }

    public static function getGeneratorClass(): string
    {
        return FormCaptchaGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Form Captcha GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        /*
         * FIXME
         * - Add proper captcha field handles
         * - Deprecate name, handle and enabled and remove in version 6
         */
        return \Craft::$app->gql->prepareFieldDefinitions([
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The forms GraphQL mutation name for submissions',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The forms GraphQL mutation handle for submissions',
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Is Captcha enabled for this form',
            ],
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The Captcha field handle',
            ],
            'version' => [
                'name' => 'version',
                'type' => Type::string(),
                'description' => 'The Captcha version',
            ],
            'triggerOnInteract' => [
                'name' => 'triggerOnInteract',
                'type' => Type::boolean(),
                'description' => 'Will the Captcha trigger on interaction',
            ],
            'failureBehavior' => [
                'name' => 'failureBehavior',
                'type' => Type::string(),
                'description' => 'The Captcha failure behavior',
            ],
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The Captcha custom error message',
            ],
            'theme' => [
                'name' => 'theme',
                'type' => Type::string(),
                'description' => 'The Captcha theme',
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
                'description' => 'The Captcha size',
            ],
            'scoreThreshold' => [
                'name' => 'scoreThreshold',
                'type' => Type::string(),
                'description' => 'The Captcha score threshold',
            ],
            'action' => [
                'name' => 'action',
                'type' => Type::string(),
                'description' => 'The Captcha action',
            ],
            'locale' => [
                'name' => 'locale',
                'type' => Type::string(),
                'description' => 'The Captcha locale',
            ],
        ], static::getName());
    }
}
