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

use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;

interface IntegrationInterface
{
    /**
     * Setting this to true will force re-fetching of all lists.
     */
    public function setForceUpdate(bool $value);

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    public function checkConnection(): bool;

    public function getId(): int;

    public function getName(): string;

    public function getLastUpdate(): \DateTime;

    /**
     * Returns the integration service provider short name
     * i.e. - Mailchimp, Constant Contact, Salesforce, etc...
     */
    public function getServiceProvider(): string;

    /**
     * Initiates the authentication process.
     */
    public function initiateAuthentication();

    /**
     * Authorizes the application and fetches the access token.
     *
     * @return string - access token
     */
    public function fetchAccessToken(): string;

    /**
     * @return null|string
     */
    public function getAccessToken();

    public function isAccessTokenUpdated(): bool;

    /**
     * @param bool $accessTokenUpdated
     *
     * @return $this
     */
    public function setAccessTokenUpdated($accessTokenUpdated);

    public function getSettings(): array;
}
