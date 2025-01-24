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

namespace Solspace\Freeform\Library\Composer\Components;

class Context implements \JsonSerializable
{
    /** @var int */
    private $page;

    /** @var string */
    private $hash;

    /**
     * Context constructor.
     */
    public function __construct(array $contextData)
    {
        $this->page = isset($contextData['page']) ? (int) $contextData['page'] : 0;
        $this->hash = $contextData['hash'] ?? Properties::FORM_HASH;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see  http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'page' => $this->page,
            'hash' => $this->hash,
        ];
    }
}
