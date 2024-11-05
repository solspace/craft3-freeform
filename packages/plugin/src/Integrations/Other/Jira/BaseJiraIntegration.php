<?php

namespace Solspace\Freeform\Integrations\Other\Jira;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\PushableInterface;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseJiraIntegration extends CRMIntegration implements JiraIntegrationInterface, OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, PushableInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Instance URL',
        instructions: 'Enter your Jira instance URL (Optional). If you do not enter a value, the first available resource URL is going to be used.',
        placeholder: 'your-instance.atlassian.net',
    )]
    protected string $instanceUrl = '';

    #[Flag(self::FLAG_INTERNAL)]
    #[Input\Hidden]
    protected string $cloudId = '';

    public function getInstanceUrl(): string
    {
        return $this->getProcessedValue($this->instanceUrl);
    }

    public function setInstanceUrl(string $instanceUrl): self
    {
        $this->instanceUrl = $instanceUrl;

        return $this;
    }

    public function getCloudId(): string
    {
        return $this->cloudId;
    }

    public function setCloudId(string $cloudId): self
    {
        $this->cloudId = $cloudId;

        return $this;
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://auth.atlassian.com/oauth/token';
    }

    public function getAuthorizeUrl(): string
    {
        return 'https://auth.atlassian.com/authorize';
    }

    public function getApiRootUrl(): string
    {
        return 'https://api.atlassian.com/ex/jira/'.$this->cloudId.'/rest/api/3';
    }

    public function checkConnection(Client $client): bool
    {
        [, $json] = $this->getJsonResponse(
            $client->get('https://api.atlassian.com/oauth/token/accessible-resources')
        );

        return \count($json) >= 1;
    }
}
