<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\interfaces\elements\Asset;
use craft\gql\interfaces\elements\User;
use craft\gql\types\DateTime;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\CaptchaResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\CsrfTokenResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\GoogleTagManagerResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\PostForwardingResolver;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\SubmissionGenerator;

class SubmissionInterface extends Element
{
    public static function getName(): string
    {
        return 'FreeformSubmissionInterface';
    }

    public static function getTypeGenerator(): string
    {
        return SubmissionGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => self::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'Freeform Submission GraphQL Interface',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));
    }

    public static function getFieldDefinitions(): array
    {
        $fieldDefinitions = array_merge([
            'finished' => [
                'name' => 'finished',
                'type' => Type::boolean(),
                'description' => 'Whether the submission is finished or not',
            ],
            'freeformPayload' => [
                'name' => 'freeformPayload',
                'type' => Type::string(),
                'description' => 'The payload of the submission',
            ],
            'hash' => [
                'name' => 'hash',
                'type' => Type::string(),
                'description' => 'The generated hash for the submission',
            ],
            'html' => [
                'name' => 'html',
                'type' => Type::string(),
                'description' => 'The generated HTML for the submission',
            ],
            'multiPage' => [
                'name' => 'multiPage',
                'type' => Type::boolean(),
                'description' => 'Whether the submission has multiple pages or not',
            ],
            'onSuccess' => [
                'name' => 'onSuccess',
                'type' => Type::string(),
                'description' => 'The success behavior of the submission',
            ],
            'returnUrl' => [
                'name' => 'returnUrl',
                'type' => Type::string(),
                'description' => 'The return URL of the submission',
            ],
            'submissionId' => [
                'name' => 'submissionId',
                'type' => Type::int(),
                'description' => 'The ID of the submission',
            ],
            'duplicate' => [
                'name' => 'duplicate',
                'type' => Type::boolean(),
                'description' => 'Whether the form submission is duplicate or not',
            ],
            'submissionToken' => [
                'name' => 'submissionToken',
                'type' => Type::string(),
                'description' => 'The generated token for the submission',
            ],
            'success' => [
                'name' => 'success',
                'type' => Type::boolean(),
                'description' => 'Whether the submission is a success or not',
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The created date for the submission',
            ],
            'isSpam' => [
                'name' => 'isSpam',
                'type' => Type::boolean(),
                'description' => 'Whether the submission is a spam or not',
            ],
            'spamReasons' => [
                'name' => 'spamReasons',
                'type' => Type::string(),
                'description' => 'Spam reasons for the submission',
            ],
            'user' => [
                'name' => 'user',
                'type' => User::getType(),
                'description' => 'The author of the submission',
            ],
            'assets' => [
                'name' => 'assets',
                'type' => Type::listOf(Asset::getType()),
                'description' => 'The assets of the submission',
            ],
            /*
             * @deprecated - this field definition is no longer used
             *
             * @remove - Freeform 6.0
             */
            'captcha' => [
                'name' => 'captcha',
                'type' => CaptchaInterface::getType(),
                'resolve' => CaptchaResolver::class.'::resolveOne',
                'description' => 'The Captcha field input name and value for this form. Deprecated. Will be removed in Freeform 6.0',
            ],
            'captchas' => [
                'name' => 'captchas',
                'type' => Type::listOf(CaptchaInterface::getType()),
                'resolve' => CaptchaResolver::class.'::resolve',
                'description' => 'List of Captcha field input names and values for this form',
            ],
            'csrfToken' => [
                'name' => 'csrfToken',
                'type' => CsrfTokenInterface::getType(),
                'resolve' => CsrfTokenResolver::class.'::resolve',
                'description' => 'The CSRF field input name and value for this form',
            ],
            'honeypot' => [
                'name' => 'honeypot',
                'type' => HoneypotInterface::getType(),
                'description' => 'The Honeypot field input name and value for this form',
            ],
            'postForwarding' => [
                'name' => 'postForwarding',
                'type' => PostForwardingInterface::getType(),
                'resolve' => PostForwardingResolver::class.'::resolve',
                'description' => 'The Post Forwarding for this form',
            ],
            'gtm' => [
                'name' => 'gtm',
                'type' => GoogleTagManagerInterface::getType(),
                'resolve' => GoogleTagManagerResolver::class.'::resolve',
                'description' => 'The Google Tag Manager for this form',
            ],
            'javascriptTest' => [
                'name' => 'javascriptTest',
                'type' => JavascriptTestInterface::getType(),
                'description' => 'The Javascript Test of the submission',
            ],
        ], parent::getFieldDefinitions());

        return \Craft::$app->getGql()->prepareFieldDefinitions(
            $fieldDefinitions,
            self::getName()
        );
    }
}
