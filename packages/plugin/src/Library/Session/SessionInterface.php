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

namespace Solspace\Freeform\Library\Session;

interface SessionInterface
{
    /**
     * @param string     $key
     * @param null|mixed $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove($key);
}
