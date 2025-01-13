<?php

namespace Solspace\Freeform\Integrations\Captchas\ReCaptcha;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Middleware;
use Solspace\Freeform\Attributes\Property\Validators\Required;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Captchas\CaptchasBundle;
use Solspace\Freeform\Library\DataObjects\SpamReason;
use Solspace\Freeform\Library\Integrations\BaseIntegration;
use Solspace\Freeform\Library\Integrations\EnabledByDefault\EnabledByDefaultTrait;
use Solspace\Freeform\Library\Integrations\Types\Captchas\CaptchaIntegrationInterface;

#[Type(
    name: 'reCAPTCHA',
    type: Type::TYPE_CAPTCHAS,
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class ReCaptcha extends BaseIntegration implements CaptchaIntegrationInterface
{
    use EnabledByDefaultTrait;

    public const VERSION_V3 = 'v3';
    public const VERSION_V2_INVISIBLE = 'v2-invisible';
    public const VERSION_V2_CHECKBOX = 'v2-checkbox';

    public const BEHAVIOR_DISPLAY_ERROR = 'display-error';
    public const BEHAVIOR_SEND_TO_SPAM = 'send-to-spam';

    #[Flag(self::FLAG_AS_HIDDEN_IN_INSTANCE)]
    #[Input\Select(
        label: 'Captcha Type',
        instructions: 'Choose the reCAPTCHA type to use. The options below are compatible with the Enterprise API and the Classic legacy keys.',
        options: [
            self::VERSION_V2_CHECKBOX => 'Challenge - Checkbox (v2)',
            self::VERSION_V2_INVISIBLE => 'Challenge - Invisible (v2)',
            self::VERSION_V3 => 'Score Based (v3)',
        ],
    )]
    private string $version = self::VERSION_V2_CHECKBOX;

    #[Required]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Flag(self::FLAG_ENCRYPTED)]
    #[Input\Text(
        label: 'Site Key',
        placeholder: 'Enter your site key',
    )]
    private ?string $siteKey = null;

    #[Required]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Flag(self::FLAG_ENCRYPTED)]
    #[Input\Text(
        label: 'Secret Key',
        placeholder: 'Enter your secret key',
    )]
    private ?string $secretKey = null;

    #[Input\Boolean(
        label: 'Only load Captcha scripts once the user interacts with the form',
        instructions: "If you'd like to have the associated Captcha scripts load only once a user begins filling out the form, enable this setting. If you'd like your forms to be ready to go at page load, disable this setting.",
    )]
    private bool $triggerOnInteract = false;

    #[Input\Select(
        label: 'Failure Behavior',
        options: [
            self::BEHAVIOR_DISPLAY_ERROR => 'Display Error Message',
            self::BEHAVIOR_SEND_TO_SPAM => 'Send to Spam Folder',
        ],
    )]
    private string $failureBehavior = self::BEHAVIOR_DISPLAY_ERROR;

    #[VisibilityFilter('values.failureBehavior === "display-error"')]
    #[Input\Text(
        label: 'Error Message',
        instructions: 'The error message to display when the Captcha validation fails.',
        placeholder: 'Please verify that you are not a robot.',
    )]
    private string $errorMessage = 'Please verify that you are not a robot.';

    #[VisibilityFilter('values.version === "v2-checkbox"')]
    #[Input\Select(
        options: [
            'light' => 'Light',
            'dark' => 'Dark',
        ],
    )]
    private string $theme = 'light';

    #[VisibilityFilter('values.version === "v2-checkbox"')]
    #[Input\Select(
        options: [
            'normal' => 'Normal',
            'compact' => 'Compact',
        ],
    )]
    private string $size = 'normal';

    #[VisibilityFilter('values.version === "v3"')]
    #[Input\Select(
        label: 'Score Threshold',
        instructions: 'The minimum score required for the Captcha to pass validation. The score is a number between 0 and 1. A score of 0.5 is generally recommended.',
        options: [
            '0.0' => '0.0',
            '0.1' => '0.1',
            '0.2' => '0.2',
            '0.3' => '0.3',
            '0.4' => '0.4',
            '0.5' => '0.5',
            '0.6' => '0.6',
            '0.7' => '0.7',
            '0.8' => '0.8',
            '0.9' => '0.9',
            '1.0' => '1.0',
        ]
    )]
    private string $scoreThreshold = '0.5';

    #[VisibilityFilter('values.version === "v3"')]
    #[Middleware('regex', ['pattern' => '[^a-zA-Z0-9_]'])]
    #[Input\Text(
        instructions: 'The action to use when validating the Captcha.',
        placeholder: 'submit',
    )]
    private string $action = 'submit';

    #[Input\Text(
        label: 'Locale',
        instructions: 'The locale to use for the Captcha as the language ID, e.g. `en`, `de`, etc. If left blank, the locale will be auto-detected.',
        placeholder: 'en',
    )]
    private string $locale = '';

    public function getSiteKey(): ?string
    {
        return $this->getProcessedValue($this->siteKey);
    }

    public function getSecretKey(): ?string
    {
        return $this->getProcessedValue($this->secretKey);
    }

    public function isTriggerOnInteract(): bool
    {
        return $this->triggerOnInteract;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getFailureBehavior(): string
    {
        return $this->failureBehavior;
    }

    public function getScoreThreshold(): string
    {
        return $this->scoreThreshold;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getLocale(): string
    {
        return strtolower($this->locale);
    }

    public function validate(Form $form): void
    {
        $settings = Freeform::getInstance()->settings->getSettingsModel();

        if ($settings->bypassSpamCheckOnLoggedInUsers && \Craft::$app->getUser()->id) {
            return;
        }

        if (!$form->isCaptchaEnabled()) {
            return;
        }

        $errors = $this->getValidationErrors($form);
        if (empty($errors)) {
            $this->logger->debug('reCAPTCHA validation passed');

            return;
        }

        $this->logger->debug('reCAPTCHA validation failed', ['errors' => $errors]);

        $behavior = $this->getFailureBehavior();
        if (self::BEHAVIOR_DISPLAY_ERROR === $behavior) {
            $form->addError($this->getErrorMessage());
        } elseif (self::BEHAVIOR_SEND_TO_SPAM === $behavior) {
            $form->markAsSpam(SpamReason::TYPE_CAPTCHA, 'reCAPTCHA - '.implode(', ', $errors));
        }
    }

    public function getHtmlTag(Form $form): string
    {
        $locale = $this->getLocale();
        if (empty($locale)) {
            $locale = \Craft::$app->locale->getLanguageID();
        }

        $attributes = CaptchasBundle::getCaptchaAttributes($form);
        $attributes
            ->replace('data-freeform-recaptcha-container')
            ->replace('data-captcha', 'recaptcha')
            ->setIfEmpty('data-site-key', $this->getSiteKey())
            ->setIfEmpty('data-theme', $this->getTheme())
            ->setIfEmpty('data-size', $this->getSize())
            ->setIfEmpty('data-lazy-load', $this->isTriggerOnInteract())
            ->setIfEmpty('data-version', $this->getVersion())
            ->setIfEmpty('data-action', $this->getAction())
            ->setIfEmpty('data-locale', $locale)
        ;

        return '<div'.$attributes.'></div>';
    }

    public function getScriptPaths(): array
    {
        $version = $this->getVersion();

        return [
            'js/scripts/front-end/captchas/recaptcha/'.$version.'.js',
        ];
    }

    public function getCaptchaHandle(): string
    {
        return 'g-recaptcha-response';
    }

    private function getValidationErrors(Form $form): array
    {
        $client = new Client();
        $secret = $this->getSecretKey();
        $captchaResponse = $this->getCaptchaResponse($form);

        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $secret,
                'response' => $captchaResponse,
                'remoteip' => \Craft::$app->request->getRemoteIP(),
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);

        $errors = [];
        if (isset($result['score'])) {
            $minScore = $this->getScoreThreshold();
            $minScore = min(1, $minScore);
            $minScore = max(0, $minScore);

            if ($result['score'] < $minScore) {
                $errors[] = 'Score check failed with ['.$result['score'].']';

                return $errors;
            }
        }

        if ($result['success']) {
            return [];
        }

        $errorCodes = $result['error-codes'];
        if (\in_array('missing-input-secret', $errorCodes, true)) {
            $errors[] = 'The secret parameter is missing.';
        }

        if (\in_array('invalid-keys', $errorCodes, true)) {
            $errors[] = 'The key parameter is invalid or malformed.';
        }

        if (\in_array('invalid-input-secret', $errorCodes, true)) {
            $errors[] = 'The secret parameter is invalid or malformed.';
        }

        if (\in_array('missing-input-response', $errorCodes, true)) {
            $errors[] = 'The response parameter is missing.';
        }

        if (\in_array('invalid-input-response', $errorCodes, true)) {
            $errors[] = 'The response parameter is invalid or malformed.';
        }

        if (\in_array('bad-request', $errorCodes, true)) {
            $errors[] = 'The request is invalid or malformed.';
        }

        if (\in_array('timeout-or-duplicate', $errorCodes, true)) {
            $errors[] = 'The response is no longer valid: either is too old or has been used previously.';
        }

        if (empty($errors)) {
            $errors = $errorCodes;
        }

        return $errors;
    }

    private function getCaptchaResponse(Form $form): ?string
    {
        if ($form->isGraphQLPosted()) {
            $arguments = $form->getGraphQLArguments();

            if (!isset($arguments['captcha'])) {
                return null;
            }

            $property = $arguments['captcha'];
            if (empty($property['name']) || empty($property['value']) || 'g-recaptcha-response' !== $property['name']) {
                return null;
            }

            return $property['value'];
        }

        return \Craft::$app->request->post('g-recaptcha-response');
    }
}
