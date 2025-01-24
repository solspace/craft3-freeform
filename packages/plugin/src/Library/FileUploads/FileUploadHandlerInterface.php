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

namespace Solspace\Freeform\Library\FileUploads;

use Solspace\Freeform\Fields\FileUploadField;
use Solspace\Freeform\Library\Composer\Components\Form;

interface FileUploadHandlerInterface
{
    /**
     * Uploads a file and flags it as "unfinalized"
     * It will be finalized only after the form has been submitted fully.
     *
     * All unfinalized files will be deleted after a certain amount of time
     *
     * @return FileUploadResponse
     */
    public function uploadFile(FileUploadField $field, Form $form);

    /**
     * Stores the unfinalized assetId in the database
     * So that it can be deleted later if the form hasn't been finalized.
     *
     * @param int $assetId
     */
    public function markAssetUnfinalized($assetId);

    /**
     * Remove all unfinalized assets which are older than the TTL
     * specified in settings.
     */
    public function cleanUpUnfinalizedAssets(int $ageInMinutes);

    /**
     * Returns an array of all file kinds
     * [type => [ext, ext, ..]
     * I.e. ["images" => ["gif", "png", "jpg", "jpeg", ..].
     *
     * @return array
     */
    public function getFileKinds();
}
