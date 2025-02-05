<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Captchas\hCaptcha\hCaptcha;
use Solspace\Freeform\Integrations\Captchas\ReCaptcha\ReCaptcha;
use Solspace\Freeform\Integrations\Captchas\Turnstile\Turnstile;
use Solspace\Freeform\Library\Integrations\Types\Captchas\CaptchaIntegrationInterface;

class CaptchaResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        $arguments = [];

        $integrations = Freeform::getInstance()->integrations->getForForm($source, Type::TYPE_CAPTCHAS);
        if (!$integrations) {
            return null;
        }

        $enabled = array_filter($integrations, fn ($integration) => $integration->isEnabled());
        if (!$enabled) {
            return null;
        }

        foreach ($integrations as $integration) {
            if (!$integration->isEnabled()) {
                continue;
            }

            $arguments[] = static::getArguments($integration);
        }

        return $arguments;
    }

    public static function resolveOne($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        $arguments = [];

        $integrations = Freeform::getInstance()->integrations->getForForm($source, Type::TYPE_CAPTCHAS);
        if (!$integrations) {
            return null;
        }

        $enabled = array_filter($integrations, fn ($integration) => $integration->isEnabled());
        if (!$enabled) {
            return null;
        }

        foreach ($integrations as $integration) {
            if (!$integration->isEnabled()) {
                continue;
            }

            // Grab the first one and return
            $arguments = static::getArguments($integration);

            break;
        }

        return $arguments;
    }

    public static function getArguments(CaptchaIntegrationInterface $integration): array
    {
        $arguments = [
            'size' => $integration->getSize(),
            'theme' => $integration->getTheme(),
            'locale' => $integration->getLocale(),
            'errorMessage' => $integration->getErrorMessage(),
            'failureBehavior' => $integration->getFailureBehavior(),
            'triggerOnInteract' => $integration->isTriggerOnInteract(),

            /*
             * @deprecated - this attribute is no longer used
             *
             * @remove - Freeform 6.0
             */
            'enabled' => true,

            /*
             * @deprecated - this attribute is no longer used
             *
             * @remove - Freeform 6.0
             */
            'handle' => 'captcha',
        ];

        if ($integration instanceof ReCaptcha) {
            $arguments['action'] = $integration->getAction();
            $arguments['version'] = $integration->getVersion();
            $arguments['scoreThreshold'] = $integration->getScoreThreshold();
            $arguments['name'] = 'g-recaptcha-response';
        }

        if ($integration instanceof hCaptcha) {
            $arguments['action'] = null;
            $arguments['version'] = $integration->getVersion();
            $arguments['scoreThreshold'] = null;
            $arguments['name'] = 'h-captcha-response';
        }

        if ($integration instanceof Turnstile) {
            $arguments['action'] = $integration->getAction();
            $arguments['version'] = null;
            $arguments['scoreThreshold'] = null;
            $arguments['name'] = 'cf-turnstile-response';
        }

        return $arguments;
    }
}
