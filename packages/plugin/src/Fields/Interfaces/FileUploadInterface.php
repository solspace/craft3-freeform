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

namespace Solspace\Freeform\Fields\Interfaces;

use craft\elements\db\AssetQuery;

interface FileUploadInterface
{
    public const FLAG_GLOBAL_PROPERTY = 'global-property';

    public function getAssets(): AssetQuery;

    public function getAssetSourceId(): ?int;

    public function getDefaultUploadLocation(): ?string;
}
