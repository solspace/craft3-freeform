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

use Psr\Log\LoggerInterface;
use Solspace\Freeform\Library\Configuration\ConfigurationInterface;
use Solspace\Freeform\Library\Database\CRMHandlerInterface;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

abstract class AbstractCRMIntegration extends AbstractIntegration implements CRMIntegrationInterface, \JsonSerializable
{
    /** @var CRMHandlerInterface */
    private $crmHandler;

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
        CRMHandlerInterface $crmHandler
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
            $crmHandler
        );

        $this->crmHandler = $crmHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthConnection(): bool
    {
        return $this instanceof CRMOAuthConnector;
    }

    /**
     * @return FieldObject[]
     */
    final public function getFields(): array
    {
        if ($this->isForceUpdate()) {
            $fields = $this->fetchFields();
            $this->crmHandler->updateFields($this, $fields);
        } else {
            $fields = $this->crmHandler->getFields($this);
        }

        return $fields;
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    abstract public function fetchFields(): array;

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        try {
            $fields = $this->getFields();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage(), ['service' => $this->getServiceProvider()]);

            $fields = [];
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'fields' => $fields,
        ];
    }
}
