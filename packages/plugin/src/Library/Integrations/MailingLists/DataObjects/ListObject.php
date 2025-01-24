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

namespace Solspace\Freeform\Library\Integrations\MailingLists\DataObjects;

use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\MailingLists\MailingListIntegrationInterface;

class ListObject implements \JsonSerializable
{
    /** @var MailingListIntegrationInterface */
    private $mailingList;

    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var int */
    private $memberCount;

    /** @var FieldObject[] */
    private $fields;

    /**
     * ListObject constructor.
     *
     * @param string        $id
     * @param string        $name
     * @param FieldObject[] $fields
     * @param int           $memberCount
     */
    public function __construct(
        MailingListIntegrationInterface $mailingList,
        $id,
        $name,
        array $fields = [],
        $memberCount = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->fields = $fields;
        $this->memberCount = $memberCount;
        $this->mailingList = $mailingList;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return FieldObject[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getMemberCount(): int
    {
        return $this->memberCount;
    }

    public function pushEmailsToList(array $emails, array $mappedValues): bool
    {
        return $this->mailingList->pushEmails($this, $emails, $mappedValues);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'fields' => $this->getFields(),
            'memberCount' => $this->getMemberCount(),
        ];
    }
}
