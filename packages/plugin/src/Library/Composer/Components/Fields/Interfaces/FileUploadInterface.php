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

namespace Solspace\Freeform\Library\Composer\Components\Fields\Interfaces;

use Solspace\Freeform\Library\Exceptions\FieldExceptions\FileUploadException;

interface FileUploadInterface
{
    /**
     * @return int
     */
    public function getAssetSourceId();

    /**
     * Attempt to upload the file to its respective location.
     *
     * @return int - asset ID
     *
     * @throws FileUploadException
     */
    public function uploadFile();
}
