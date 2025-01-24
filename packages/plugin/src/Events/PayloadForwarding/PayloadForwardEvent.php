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

namespace Solspace\Freeform\Events\PayloadForwarding;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Solspace\Freeform\Events\CancelableArrayableEvent;

class PayloadForwardEvent extends CancelableArrayableEvent
{
    /** @var Client */
    private $client;

    /** @var Request */
    private $request;

    /** @var string */
    private $url;

    /** @var array */
    private $options;

    /** @var array */
    private $payload;

    /**
     * PayloadForwardEvent constructor.
     */
    public function __construct(Client $client, Request $request, string $url, array $options, array $payload)
    {
        $this->client = $client;
        $this->request = $request;
        $this->url = $url;
        $this->options = $options;
        $this->payload = $payload;

        parent::__construct([]);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return PayloadForwardEvent
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return PayloadForwardEvent
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return PayloadForwardEvent
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed $value
     */
    public function addOption(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @return PayloadForwardEvent
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param mixed $value
     */
    public function addPayload(string $key, $value): self
    {
        $this->payload[$key] = $value;

        return $this;
    }

    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }
}
