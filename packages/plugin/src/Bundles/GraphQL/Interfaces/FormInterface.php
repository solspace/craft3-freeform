<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Arguments\FieldArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\CsrfTokenInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\FormCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\HoneypotInterface;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\CsrfTokenResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\FieldResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\FormCaptchaResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\GoogleTagManagerResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\HoneypotResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\PageResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\PostForwardingResolver;
use Solspace\Freeform\Bundles\GraphQL\Resolvers\RulesResolver;
use Solspace\Freeform\Bundles\GraphQL\Types\FormType;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\FormGenerator;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Freeform;

class FormInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformFormInterface';
    }

    public static function getTypeClass(): string
    {
        return FormType::class;
    }

    public static function getGeneratorClass(): string
    {
        return FormGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Form GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions([
            'id' => [
                'name' => 'id',
                'type' => Type::int(),
                'description' => "The form's ID",
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => "The form's UID",
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => "The form's name",
                'resolve' => function ($source) {
                    return $source->getSettings()->name;
                },
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => "The form's handle",
                'resolve' => function ($source) {
                    return $source->getSettings()->handle;
                },
            ],
            'type' => [
                'name' => 'type',
                'type' => Type::string(),
                'description' => "The form's type",
                'resolve' => function ($source) {
                    return $source->getSettings()->type;
                },
            ],
            'color' => [
                'name' => 'color',
                'type' => Type::string(),
                'description' => "The form's color hex",
                'resolve' => function ($source) {
                    return $source->getSettings()->color;
                },
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => "The form's handle",
                'resolve' => function ($source) {
                    return $source->getSettings()->description;
                },
            ],
            'returnUrl' => [
                'name' => 'returnUrl',
                'type' => Type::string(),
                'description' => "The form's return URL",
                'resolve' => function ($source) {
                    return $source->getSettings()->returnUrl;
                },
            ],
            'storeData' => [
                'name' => 'storeData',
                'type' => Type::boolean(),
                'description' => 'Whether the form stores submissions or not',
                'resolve' => function ($source) {
                    return $source->getSettings()->storeData;
                },
            ],
            'defaultStatus' => [
                'name' => 'defaultStatus',
                'type' => Type::int(),
                'description' => "The form's default status ID",
                'resolve' => function ($source) {
                    return $source->getSettings()->defaultStatus;
                },
            ],
            'formTemplate' => [
                'name' => 'formTemplate',
                'type' => Type::string(),
                'description' => "The form's formatting template filename",
                'resolve' => function ($source) {
                    return $source->getSettings()->formattingTemplate;
                },
            ],
            'hash' => [
                'name' => 'hash',
                'type' => Type::string(),
                'description' => "The form's hash needed to submit forms",
                'resolve' => function ($source) {
                    return $source->getHash();
                },
            ],
            'submissionTitleFormat' => [
                'name' => 'submissionTitleFormat',
                'type' => Type::string(),
                'description' => 'Title format used for new submission titles',
                'resolve' => function ($source) {
                    return $source->getSettings()->submissionTitle;
                },
            ],
            'submissionMutationName' => [
                'name' => 'submissionMutationName',
                'type' => Type::string(),
                'description' => 'The forms GraphQL mutation name for submissions',
                'resolve' => function ($source) {
                    return Submission::gqlMutationNameByContext($source);
                },
            ],
            'ipCollectingEnabled' => [
                'name' => 'ipCollectingEnabled',
                'type' => Type::boolean(),
                'description' => 'Are the IP addresses being stored',
                'resolve' => function ($source) {
                    return $source->getSettings()->collectIpAddresses;
                },
            ],
            'ajax' => [
                'name' => 'ajax',
                'type' => Type::boolean(),
                'description' => 'Is the ajax enabled for this form',
                'resolve' => function ($source) {
                    return $source->getSettings()->ajax;
                },
            ],
            'showProcessingSpinner' => [
                'name' => 'showProcessingSpinner',
                'type' => Type::boolean(),
                'description' => 'Should the submit button show a spinner when submitting',
                'resolve' => function ($source) {
                    return $source->getSettings()->showProcessingSpinner;
                },
            ],
            'showProcessingText' => [
                'name' => 'showProcessingText',
                'type' => Type::boolean(),
                'description' => 'Should the submit button change the button label while submitting',
                'resolve' => function ($source) {
                    return $source->getSettings()->showProcessingText;
                },
            ],
            'processingText' => [
                'name' => 'processingText',
                'type' => Type::string(),
                'description' => 'The submit button processing label text',
                'resolve' => function ($source) {
                    return $source->getSettings()->processingText;
                },
            ],
            'captcha' => [
                'name' => 'captcha',
                'type' => FormCaptchaInterface::getType(),
                'resolve' => FormCaptchaResolver::class.'::resolve',
                'description' => 'The Captcha for this form',
            ],
            'honeypot' => [
                'name' => 'honeypot',
                'type' => HoneypotInterface::getType(),
                'resolve' => HoneypotResolver::class.'::resolve',
                'description' => 'The Honeypot for this form',
            ],
            'csrfToken' => [
                'name' => 'csrfToken',
                'type' => CsrfTokenInterface::getType(),
                'resolve' => CsrfTokenResolver::class.'::resolve',
                'description' => 'The CSRF Token for this form',
            ],
            'pages' => [
                'name' => 'pages',
                'type' => Type::listOf(PageInterface::getType()),
                'resolve' => PageResolver::class.'::resolve',
                'description' => 'The form’s pages.',
            ],
            'fields' => [
                'name' => 'fields',
                'type' => Type::listOf(FieldInterface::getType()),
                'resolve' => FieldResolver::class.'::resolve',
                'args' => FieldArguments::getArguments(),
                'description' => "Form's fields",
            ],
            'successMessage' => [
                'name' => 'successMessage',
                'type' => Type::string(),
                'description' => 'The form’s success message',
                'resolve' => function ($source) {
                    return $source->getSettings()->successMessage;
                },
            ],
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The form’s error message',
                'resolve' => function ($source) {
                    return $source->getSettings()->errorMessage;
                },
            ],
            'disableSubmit' => [
                'name' => 'disableSubmit',
                'type' => Type::boolean(),
                'description' => 'Should the form’s submit button be disabled when the form is submitted',
                'resolve' => function () {
                    return Freeform::getInstance()->forms->isFormSubmitDisable();
                },
            ],
            'disableReset' => [
                'name' => 'disableReset',
                'type' => Type::boolean(),
                'description' => 'Should the form’s submit button be disabled state be reset',
                'resolve' => function ($source) {
                    return $source->isAjaxResetDisabled();
                },
            ],
            'enctype' => [
                'name' => 'enctype',
                'type' => Type::string(),
                'description' => 'The form’s enctype',
                'resolve' => function ($source) {
                    $isMultipart = $source->getLayout()->hasFields(FileUploadInterface::class);

                    return $isMultipart ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
                },
            ],
            'rules' => [
                'name' => 'rules',
                'type' => RulesInterface::getType(),
                'resolve' => RulesResolver::class.'::resolve',
                'description' => 'The rules for this form',
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
        ], static::getName());
    }
}
