<?php

namespace Solspace\Freeform\Bundles\Integrations\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\Events\Integrations\FailedRequestEvent;
use Solspace\Freeform\Events\Integrations\GetAuthorizedClientEvent;
use Solspace\Freeform\Events\Integrations\OAuth2\TokenPayloadEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2IssuedAtMilliseconds;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use yii\base\Event;

class OAuth2RefreshTokenBundle extends FeatureBundle
{
    private const DEFAULT_TOKEN_DURATION = 3600; // 1 hour
    private const CHECK_BUFFER = 120;            // 2 minutes

    private static array $refreshedTokens = [];

    public function __construct()
    {
        Event::on(
            OAuth2ConnectorInterface::class,
            OAuth2ConnectorInterface::EVENT_AFTER_AUTHORIZE,
            [$this, 'onFetchTokens']
        );

        Event::on(
            OAuth2RefreshTokenInterface::class,
            OAuth2RefreshTokenInterface::EVENT_AFTER_REFRESH,
            [$this, 'onAfterRefresh']
        );

        Event::on(
            IntegrationClientProvider::class,
            IntegrationClientProvider::EVENT_GET_CLIENT,
            [$this, 'refreshIfExpired']
        );

        Event::on(
            IntegrationInterface::class,
            IntegrationInterface::EVENT_ON_FAILED_REQUEST,
            [$this, 'forceRefresh']
        );
    }

    public static function getPriority(): int
    {
        return 500;
    }

    public function onFetchTokens(TokenPayloadEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof OAuth2RefreshTokenInterface) {
            return;
        }

        $payload = $event->getResponsePayload();
        if (!isset($payload->refresh_token)) {
            throw new IntegrationException(
                Freeform::t(
                    "No 'refresh_token' present in auth response for {serviceProvider}. Enable offline-access for your app.",
                    ['serviceProvider' => $integration->getServiceProvider()]
                )
            );
        }

        $integration->setRefreshToken($payload->refresh_token);
        $integration->setIssuedAt($payload->issued_at ?? time());
        $integration->setExpiresIn($payload->expires_in ?? self::DEFAULT_TOKEN_DURATION);
    }

    public function onAfterRefresh(TokenPayloadEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof OAuth2RefreshTokenInterface) {
            return;
        }

        $payload = $event->getResponsePayload();
        if (!isset($payload->access_token)) {
            throw new IntegrationException(
                Freeform::t(
                    "No 'access_token' present in refresh token response for {serviceProvider}.",
                    ['serviceProvider' => $integration->getServiceProvider()]
                )
            );
        }

        if (isset($payload->refresh_token)) {
            $integration->setRefreshToken($payload->refresh_token);
        }

        $integration->setAccessToken($payload->access_token);
        $integration->setIssuedAt($payload->issued_at ?? time());
        $integration->setExpiresIn($payload->expires_in ?? self::DEFAULT_TOKEN_DURATION);
    }

    public function refreshIfExpired(GetAuthorizedClientEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof OAuth2RefreshTokenInterface) {
            return;
        }

        $issuedAt = $integration->getIssuedAt();
        $expiresIn = $integration->getExpiresIn();

        if ($integration instanceof OAuth2IssuedAtMilliseconds) {
            $issuedAt = (int) ($issuedAt / 1000);
        }

        if ($issuedAt + $expiresIn - self::CHECK_BUFFER > time()) {
            return;
        }

        $this->refreshToken($integration);
    }

    public function forceRefresh(FailedRequestEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $integration = $event->getIntegration();
        if (!$integration instanceof OAuth2RefreshTokenInterface) {
            return;
        }

        $exception = $event->getException();
        if (401 !== $exception->getCode()) {
            return;
        }

        $this->refreshToken($integration);
        $event->triggerRetry();
    }

    private function refreshToken(OAuth2RefreshTokenInterface $integration): void
    {
        if (\in_array($integration->getId(), self::$refreshedTokens, true)) {
            return;
        }

        $clientId = $integration->getClientId();
        $clientSecret = $integration->getClientSecret();
        $refreshToken = $integration->getRefreshToken();

        if (!$clientId || !$clientSecret || !$refreshToken) {
            throw new IntegrationException('Some or all of the configuration values are missing');
        }

        $client = new Client();

        $payload = [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ];

        try {
            $response = $client->post($integration->getAccessTokenUrl(), ['form_params' => $payload]);
            $responsePayload = json_decode($response->getBody(), false);

            Event::trigger(
                OAuth2RefreshTokenInterface::class,
                OAuth2RefreshTokenInterface::EVENT_AFTER_REFRESH,
                new TokenPayloadEvent($integration, $responsePayload)
            );

            self::$refreshedTokens[] = $integration->getId();

            $integrationsService = Freeform::getInstance()->integrations;
            $model = $integrationsService->getById($integration->getId());
            $integrationsService->save($model, $integration);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();

            Freeform::getInstance()->logger->getLogger(FreeformLogger::INTEGRATION)->error($responseBody, ['exception' => $e->getMessage()]);

            throw $e;
        }
    }
}
