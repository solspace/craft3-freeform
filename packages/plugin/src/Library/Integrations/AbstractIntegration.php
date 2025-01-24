<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Integrations;

use Psr\Log\LoggerInterface;
use Solspace\Freeform\Fields\Pro\DatetimeField;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Configuration\ConfigurationInterface;
use Solspace\Freeform\Library\Database\IntegrationHandlerInterface;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

abstract class AbstractIntegration implements IntegrationInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var \DateTime */
    private $lastUpdate;

    /** @var string */
    private $accessToken;

    /** @var bool */
    private $accessTokenUpdated;

    /** @var array */
    private $settings;

    /** @var ConfigurationInterface */
    private $configuration;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $forceUpdate;

    /** @var TranslatorInterface */
    private $translator;

    /** @var IntegrationHandlerInterface */
    private $handler;

    /**
     * @param int        $id
     * @param string     $name
     * @param string     $accessToken
     * @param null|array $settings
     */
    public function __construct(
        $id,
        $name,
        \DateTime $lastUpdate,
        $accessToken,
        $settings,
        LoggerInterface $logger,
        ConfigurationInterface $configuration,
        TranslatorInterface $translator,
        IntegrationHandlerInterface $handler
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastUpdate = $lastUpdate;
        $this->accessToken = $accessToken;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->configuration = $configuration;
        $this->translator = $translator;
        $this->handler = $handler;
    }

    /**
     * Returns a list of additional settings for this integration
     * Could be used for anything, like - AccessTokens.
     *
     * @return SettingBlueprint[]
     */
    public static function getSettingBlueprints(): array
    {
        return [];
    }

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    abstract public function checkConnection(): bool;

    /**
     * Returns true if this connection uses the OAuth2 protocol.
     */
    abstract public function isOAuthConnection(): bool;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastUpdate(): \DateTime
    {
        return $this->lastUpdate;
    }

    /**
     * Setting this to true will force re-fetching of all lists.
     */
    final public function setForceUpdate(bool $value)
    {
        $this->forceUpdate = $value;
    }

    final public function isForceUpdate(): bool
    {
        return (bool) $this->forceUpdate;
    }

    /**
     * Returns the MailingList service provider short name
     * i.e. - Mailchimp, Constant Contact, etc...
     *
     * @throws \ReflectionException
     */
    public function getServiceProvider(): string
    {
        $reflection = new \ReflectionClass($this);

        return $reflection->getShortName();
    }

    /**
     * A method that initiates the authentication.
     */
    abstract public function initiateAuthentication();

    /**
     * Authorizes the application
     * Returns the access_token.
     *
     * @throws IntegrationException
     */
    abstract public function fetchAccessToken(): string;

    /**
     * Perform anything necessary before this integration is saved.
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
    }

    final public function getSettings(): array
    {
        return $this->settings ?: [];
    }

    /**
     * @return null|string
     */
    final public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function isAccessTokenUpdated(): bool
    {
        return $this->accessTokenUpdated ?? false;
    }

    /**
     * @param bool $accessTokenUpdated
     *
     * @return $this
     */
    public function setAccessTokenUpdated($accessTokenUpdated)
    {
        $this->accessTokenUpdated = (bool) $accessTokenUpdated;

        return $this;
    }

    /**
     * @return array|bool|string
     */
    public function convertCustomFieldValue(FieldObject $fieldObject, AbstractField $field)
    {
        if (FieldObject::TYPE_ARRAY === $fieldObject->getType()) {
            $value = $field->getValue();
        } else {
            $value = $field->getValueAsString(false);
        }

        switch ($fieldObject->getType()) {
            case FieldObject::TYPE_NUMERIC:
                return (int) preg_replace('/\D/', '', $value) ?: '';

            case FieldObject::TYPE_FLOAT:
                return (float) preg_replace('/[^0-9,.]/', '', $value) ?: '';

            case FieldObject::TYPE_DATE:
                if ($field instanceof DatetimeField) {
                    $carbon = $field->getCarbon();
                    if ($carbon) {
                        return $carbon->toDateString();
                    }
                }

                return (string) $value;

            case FieldObject::TYPE_DATETIME:
                if ($field instanceof DatetimeField) {
                    $carbon = $field->getCarbon();
                    if ($carbon) {
                        return $carbon->toAtomString();
                    }
                }

                return (string) $value;

            case FieldObject::TYPE_TIMESTAMP:
            case FieldObject::TYPE_MICROTIME:
                if ($field instanceof DatetimeField) {
                    $carbon = $field->getCarbonUtc();
                    if ($carbon) {
                        if (DatetimeField::DATETIME_TYPE_DATE === $field->getDateTimeType()) {
                            $carbon->setTime(0, 0);
                        }

                        $timestamp = $carbon->getTimestamp();
                        if (FieldObject::TYPE_MICROTIME === $fieldObject->getType()) {
                            $timestamp *= 1000;
                        }

                        return $timestamp;
                    }
                }

                return (int) $value;

            case FieldObject::TYPE_BOOLEAN:
                return (bool) $value;

            case FieldObject::TYPE_ARRAY:
                if (!\is_array($value)) {
                    $value = [$value];
                }

                return $value;

            case FieldObject::TYPE_STRING:
            default:
                return (string) $value;
        }
    }

    final protected function setAccessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    protected function getHandler(): IntegrationHandlerInterface
    {
        return $this->handler;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    abstract protected function getApiRootUrl(): string;

    /**
     * Returns a combined URL of api root + endpoint.
     *
     * @param string $endpoint
     */
    final protected function getEndpoint($endpoint): string
    {
        $root = rtrim($this->getApiRootUrl(), '/');
        $endpoint = ltrim($endpoint, '/');

        return "{$root}/{$endpoint}";
    }

    /**
     * Get settings by handle.
     *
     * @param string $handle
     *
     * @return null|mixed
     *
     * @throws IntegrationException
     */
    final protected function getSetting($handle)
    {
        $blueprint = $this->getSettingBlueprint($handle);

        if (SettingBlueprint::TYPE_CONFIG === $blueprint->getType()) {
            return $this->configuration->get($blueprint->getHandle());
        }

        if (isset($this->settings[$handle])) {
            if (SettingBlueprint::TYPE_BOOL === $blueprint->getType()) {
                return (bool) $this->settings[$handle];
            }

            return \Craft::parseEnv($this->settings[$handle]);
        }

        if ($blueprint->isRequired()) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    '{setting} setting not specified',
                    ['setting' => $blueprint->getLabel()]
                )
            );
        }

        return null;
    }

    /**
     * @param string $handle
     * @param mixed  $value
     *
     * @return $this
     *
     * @throws IntegrationException
     */
    final protected function setSetting($handle, $value)
    {
        // Check for blueprint validity
        $this->getSettingBlueprint($handle);

        $this->settings[$handle] = $value;

        return $this;
    }

    /**
     * @param string $handle
     *
     * @throws IntegrationException
     */
    private function getSettingBlueprint($handle): SettingBlueprint
    {
        foreach (static::getSettingBlueprints() as $blueprint) {
            if ($blueprint->getHandle() === $handle) {
                return $blueprint;
            }
        }

        throw new IntegrationException(
            $this->getTranslator()->translate(
                'Could not find setting blueprints for {handle}',
                ['handle' => $handle]
            )
        );
    }
}
