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

namespace Solspace\Freeform\Library\Composer\Components\Fields\Traits;

trait FileUploadTrait
{
    /** @var int */
    protected $assetSourceId;

    /** @var int */
    protected $defaultUploadLocation;

    /**
     * @return null|int
     */
    public function getAssetSourceId()
    {
        return $this->assetSourceId;
    }

    public function getDefaultUploadLocation()
    {
        return $this->defaultUploadLocation;
    }
}
