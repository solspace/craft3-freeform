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

namespace Solspace\Freeform\Library\Integrations\CRM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

abstract class CRMOAuthConnector extends AbstractCRMIntegration
{
    public const SETTING_CLIENT_ID = 'client_id';
    public const SETTING_CLIENT_SECRET = 'client_secret';
    public const SETTING_RETURN_URI = 'return_uri';

    /**
     * Returns a list of additional settings for this integration
     * Could be used for anything, like - AccessTokens.
     *
     * @return SettingBlueprint[]
     */
    public static function getSettingBlueprints(): array
    {
        return [
            new SettingBlueprint(
                SettingBlueprint::TYPE_AUTO,
                self::SETTING_RETURN_URI,
                'OAuth 2.0 Return URI',
                'You must specify this as the Return URI in your app settings to be able to authorize your credentials. DO NOT CHANGE THIS.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_CLIENT_ID,
                'Client ID',
                'Enter the Client ID of your app in here',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_CLIENT_SECRET,
                'Client Secret',
                'Enter the Client Secret of your app here',
                true
            ),
        ];
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
        $data = [
            'response_type' => 'code',
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getReturnUri(),
        ];

        $queryString = http_build_query($data);

        header('Location: '.$this->getAuthorizeUrl().'?'.$queryString);

        exit;
    }

    /**
     * @throws IntegrationException
     */
    public function fetchAccessToken(): string
    {
        $client = new Client();

        $code = $_GET['code'] ?? null;
        $this->onBeforeFetchAccessToken($code);

        if (null === $code) {
            return '';
        }

        $payload = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->getSetting(self::SETTING_CLIENT_ID),
            'client_secret' => $this->getSetting(self::SETTING_CLIENT_SECRET),
            'redirect_uri' => $this->getSetting(self::SETTING_RETURN_URI),
            'code' => $code,
        ];

        try {
            $response = $client->post(
                $this->getAccessTokenUrl(),
                ['form_params' => $payload]
            );
        } catch (RequestException $e) {
            throw new IntegrationException((string) $e->getResponse()->getBody());
        }

        $json = json_decode((string) $response->getBody());

        if (!isset($json->access_token)) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    "No 'access_token' present in auth response for {serviceProvider}",
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        if ($this instanceof RefreshTokenInterface && !isset($json->refresh_token)) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    "No 'refresh_token' present in auth response for {serviceProvider}. Enable offline-access for your app.",
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        $this->setAccessToken($json->access_token);
        $this->onAfterFetchAccessToken($json);

        return $this->getAccessToken();
    }

    /**
     * @param null|string $code
     */
    protected function onBeforeFetchAccessToken(&$code = null)
    {
    }

    protected function onAfterFetchAccessToken(\stdClass $responseData)
    {
    }

    protected function getClientId(): string
    {
        return $this->getSetting(self::SETTING_CLIENT_ID);
    }

    protected function getClientSecret(): string
    {
        return $this->getSetting(self::SETTING_CLIENT_SECRET);
    }

    protected function getReturnUri(): string
    {
        return $this->getSetting(self::SETTING_RETURN_URI);
    }

    /**
     * URL pointing to the OAuth2 authorization endpoint.
     */
    abstract protected function getAuthorizeUrl(): string;

    /**
     * URL pointing to the OAuth2 access token endpoint.
     */
    abstract protected function getAccessTokenUrl(): string;
}
