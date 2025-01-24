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

namespace Solspace\Freeform\Library\Composer\Components\Properties;

class IntegrationProperties extends AbstractProperties
{
    /** @var int */
    protected $integrationId;

    /** @var array */
    protected $mapping;

    /**
     * @return null|int
     */
    public function getIntegrationId()
    {
        return (int) $this->integrationId ?: null;
    }

    /**
     * @return null|array
     */
    public function getMapping()
    {
        return !empty($this->mapping) ? $this->mapping : null;
    }

    /**
     * Return a list of all property fields and their type.
     *
     * [propertyKey => propertyType, ..]
     * E.g. ["name" => "string", ..]
     */
    protected function getPropertyManifest(): array
    {
        return [
            'integrationId' => self::TYPE_INTEGER,
            'mapping' => self::TYPE_ARRAY,
        ];
    }
}
