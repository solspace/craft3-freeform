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

namespace Solspace\Freeform\Library\Integrations\MailingLists;

use Psr\Log\LoggerInterface;
use Solspace\Freeform\Library\Configuration\ConfigurationInterface;
use Solspace\Freeform\Library\Database\MailingListHandlerInterface;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Exceptions\Integrations\ListNotFoundException;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

abstract class AbstractMailingListIntegration extends AbstractIntegration implements MailingListIntegrationInterface, IntegrationInterface, \JsonSerializable
{
    public const TYPE = 'mailing_list';

    /** @var MailingListHandlerInterface */
    private $mailingListHandler;

    /**
     * AbstractMailingList constructor.
     *
     * @param int        $id
     * @param string     $name
     * @param string     $accessToken
     * @param null|array $settings
     */
    final public function __construct(
        $id,
        $name,
        \DateTime $lastUpdate,
        $accessToken,
        $settings,
        LoggerInterface $logger,
        ConfigurationInterface $configuration,
        TranslatorInterface $translator,
        MailingListHandlerInterface $mailingListHandler
    ) {
        parent::__construct(
            $id,
            $name,
            $lastUpdate,
            $accessToken,
            $settings,
            $logger,
            $configuration,
            $translator,
            $mailingListHandler
        );

        $this->mailingListHandler = $mailingListHandler;
    }

    /**
     * {@inheritDoc}
     */
    public static function isInstallable(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthConnection(): bool
    {
        return $this instanceof MailingListOAuthConnector;
    }

    /**
     * @return ListObject[]
     */
    final public function getLists(): array
    {
        if ($this->isForceUpdate()) {
            $lists = $this->fetchLists();
            $this->mailingListHandler->updateLists($this, $lists);
        } else {
            $lists = $this->mailingListHandler->getLists($this);
        }

        return $lists;
    }

    /**
     * @param string $listId
     *
     * @throws ListNotFoundException
     */
    final public function getListById($listId): ListObject
    {
        return $this->mailingListHandler->getListById($this, $listId);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        try {
            $lists = $this->getLists();
        } catch (\Exception $e) {
            $lists = [];
        }

        return [
            'integrationId' => $this->getId(),
            'resourceId' => '',
            'type' => self::TYPE,
            'source' => $this->getServiceProvider(),
            'name' => $this->getName(),
            'label' => 'Opt-in mailing list "'.$this->getName().'"',
            'emailFieldHash' => '',
            'lists' => $lists,
        ];
    }

    /**
     * Makes an API call that fetches mailing lists
     * Builds ListObject objects based on the results
     * And returns them.
     *
     * @return ListObject[]
     */
    abstract protected function fetchLists(): array;

    /**
     * Fetch all custom fields for each list.
     *
     * @param string $listId
     *
     * @return FieldObject[]
     *
     * @throws IntegrationException
     */
    abstract protected function fetchFields($listId): array;
}
