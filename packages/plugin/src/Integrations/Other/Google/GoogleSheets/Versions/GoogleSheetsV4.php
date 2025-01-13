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

namespace Solspace\Freeform\Integrations\Other\Google\GoogleSheets\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\Google\GoogleSheets\BaseGoogleSheetsIntegration;
use Solspace\Freeform\Integrations\Other\Google\GoogleSheets\Utilities\SheetsHelper;

#[Type(
    name: 'Google Sheets',
    type: Type::TYPE_OTHER,
    version: 'v4',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class GoogleSheetsV4 extends BaseGoogleSheetsIntegration
{
    protected const API_VERSION = 'v4';

    public function getApiRootUrl(): string
    {
        return parent::getApiRootUrl().'/'.self::API_VERSION;
    }

    public function getSheetNames(string $googleSheetsId, Client $client): array
    {
        $url = $this->getEndpoint("spreadsheets/{$googleSheetsId}?fields=sheets(properties)");

        $names = [];
        [, $json] = $this->getJsonResponse($client->get($url));

        foreach ($json->sheets as $sheet) {
            $names[] = $sheet->properties->title;
        }

        $this->logger->debug('Retrieved sheet names', ['names' => $names]);

        return $names;
    }

    public function getSheetColumnsCount(string $googleSheetsId, string $sheetName, Client $client): int
    {
        $url = $this->getEndpoint("spreadsheets/{$googleSheetsId}?fields=sheets(properties)");

        [, $json] = $this->getJsonResponse($client->get($url));

        foreach ($json->sheets as $sheetData) {
            if ($sheetData->properties->title === $sheetName) {
                return $sheetData->properties->gridProperties->columnCount;
            }
        }

        return $json->sheets[0]->properties->gridProperties->columnCount;
    }

    public function push(Form $form, Client $client): void
    {
        $query = http_build_query([
            'valueInputOption' => $this->isProcessValues() ? 'USER_ENTERED' : 'RAW',
            'insertDataOption' => $this->getInsertOption() ?? self::INSERT_OPTION_OVERWRITE,
        ]);

        $spreadsheetId = $this->getGoogleSheetsId();
        $sheet = $this->getSheet() ?: null;

        $mapping = $this->processMapping($form, $this->getFieldMapping(), 'default');

        $firstColumn = min(9999999, ...array_keys($mapping));
        $lastColumn = max(0, ...array_keys($mapping));

        $offset = $this->getOffset() ?: 0;
        $rangeStart = SheetsHelper::getA1($firstColumn, $offset);
        $rangeEnd = SheetsHelper::getA1($lastColumn, $offset);
        $range = $rangeStart.':'.$rangeEnd;

        $values = [];
        for ($i = $firstColumn; $i <= $lastColumn; ++$i) {
            if (\array_key_exists($i, $mapping)) {
                $values[] = $mapping[$i];
            } else {
                $values[] = null;
            }
        }

        $url = $this->getEndpoint("spreadsheets/{$spreadsheetId}/values/{$sheet}!{$range}:append?{$query}");
        $client->post($url, ['json' => ['values' => [$values]]]);

        $this->logger->info('Pushed data to Google Sheets');
        $this->logger->debug('With Data', ['spreadsheetId' => $spreadsheetId, 'sheet' => $sheet, 'range' => $range, 'values' => $values]);
    }
}
