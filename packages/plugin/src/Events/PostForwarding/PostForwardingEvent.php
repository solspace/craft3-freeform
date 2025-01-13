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

namespace Solspace\Freeform\Events\PostForwarding;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Solspace\Freeform\Events\CancelableArrayableEvent;

class PostForwardingEvent extends CancelableArrayableEvent
{
    public function __construct(
        private Client $client,
        private Request $request,
        private string $url,
        private array $options,
        private array $payload
    ) {
        parent::__construct([]);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function addPayload(string $key, mixed $value): self
    {
        $this->payload[$key] = $value;

        return $this;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
