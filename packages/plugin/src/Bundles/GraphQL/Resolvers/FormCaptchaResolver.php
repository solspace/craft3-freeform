<?php

/*
 * FIXME
 * - Deprecate captcha resolver and remove in version 6
 * - Add proper reCaptcha, hCaptcha and turnstiles resolvers
 */

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Captchas\hCaptcha\hCaptcha;
use Solspace\Freeform\Integrations\Captchas\ReCaptcha\ReCaptcha;
use Solspace\Freeform\Integrations\Captchas\Turnstile\Turnstile;

class FormCaptchaResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        $integrations = Freeform::getInstance()->integrations->getForForm($source, Type::TYPE_CAPTCHAS);
        if (!$integrations) {
            return null;
        }

        $arguments = [];

        foreach ($integrations as $integration) {
            if (!$integration->isEnabled()) {
                continue;
            }

            // FIXME - remove in version 6
            $arguments['enabled'] = true;
            $arguments['handle'] = 'captcha';

            $arguments['triggerOnInteract'] = $integration->isTriggerOnInteract();
            $arguments['failureBehavior'] = $integration->getFailureBehavior();
            $arguments['errorMessage'] = $integration->getErrorMessage();
            $arguments['theme'] = $integration->getTheme();
            $arguments['size'] = $integration->getSize();
            $arguments['locale'] = $integration->getLocale();

            // FIXME - remove in version 6
            if ($integration instanceof ReCaptcha) {
                $arguments['version'] = $integration->getVersion();
                $arguments['action'] = $integration->getAction();
                $arguments['scoreThreshold'] = $integration->getScoreThreshold();
                $arguments['fieldHandle'] = 'g-recaptcha-response';
                $arguments['name'] = 'g-recaptcha-response';
            }

            if ($integration instanceof hCaptcha) {
                $arguments['version'] = $integration->getVersion();
                $arguments['fieldHandle'] = 'h-captcha-response';
                $arguments['name'] = 'h-captcha-response';
            }

            if ($integration instanceof Turnstile) {
                $arguments['action'] = $integration->getAction();
                $arguments['fieldHandle'] = 'cf-turnstile-response';
                $arguments['name'] = 'cf-turnstile-response';
            }
        }

        return $arguments;
    }
}
