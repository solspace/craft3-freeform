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

namespace Solspace\Freeform\Library\Codepack\Components;

interface ComponentInterface
{
    /**
     * ComponentInterface constructor.
     */
    public function __construct(string $location);

    /**
     * Calls the installation of this component.
     */
    public function install(?string $prefix = null);
}
