<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Integrations\Types\Other;

use GuzzleHttp\Client;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;

interface GoogleSheetsIntegrationInterface extends APIIntegrationInterface
{
    public function getGoogleSheetsId(): ?string;

    public function push(Form $form, Client $client): void;

    public function getSheetNames(string $googleSheetsId, Client $client): array;

    public function getSheetColumnsCount(string $googleSheetsId, string $sheetName, Client $client): int;
}
