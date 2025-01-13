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

use Solspace\Freeform\Attributes\Integration\Type;

interface IntegrationInterface
{
    public const EVENT_AFTER_RESPONSE = 'after-response';
    public const EVENT_ON_FAILED_REQUEST = 'on-failed-request';

    public const FLAG_GLOBAL_PROPERTY = 'global-property';
    public const FLAG_AS_HIDDEN_IN_INSTANCE = 'as-hidden-in-instance';
    public const FLAG_AS_READONLY_IN_INSTANCE = 'as-readonly-in-instance';
    public const FLAG_INSTANCE_ONLY = 'instance';
    public const FLAG_INTERNAL = 'internal';
    public const FLAG_ENCRYPTED = 'encrypted';
    public const FLAG_READONLY = 'readonly';

    public function getId(): ?int;

    public function getUid(): ?string;

    public function setId(int $id): self;

    public function isEnabled(): bool;

    public function getHandle(): ?string;

    public function getName(): ?string;

    /**
     * Returns the integration service provider short name
     * i.e. - MailChimp, Constant Contact, Salesforce, etc...
     */
    public function getServiceProvider(): string;

    public function getTypeDefinition(): Type;
}
