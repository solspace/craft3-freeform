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

namespace Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects;

class ListObject
{
    public function __construct(
        private string $resourceId,
        private string $name,
        private int $memberCount = 0,
        private ?int $id = null,
    ) {}

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMemberCount(): int
    {
        return $this->memberCount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
