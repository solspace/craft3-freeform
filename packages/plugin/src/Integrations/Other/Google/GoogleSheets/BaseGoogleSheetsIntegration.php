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

namespace Solspace\Freeform\Integrations\Other\Google\GoogleSheets;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\Validators\Required;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Integrations\Other\Google\GoogleSheets\Utilities\SheetsHelper;
use Solspace\Freeform\Library\Integrations\APIIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2IssuedAtMilliseconds;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\PushableInterface;
use Solspace\Freeform\Library\Integrations\Types\Other\GoogleSheetsIntegrationInterface;

abstract class BaseGoogleSheetsIntegration extends APIIntegration implements OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, OAuth2IssuedAtMilliseconds, GoogleSheetsIntegrationInterface, PushableInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'GoogleSheets';

    protected const INSERT_OPTION_OVERWRITE = 'OVERWRITE';
    protected const INSERT_OPTION_INSERT_ROWS = 'INSERT_ROWS';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[Required]
    #[Input\Text(
        label: 'Google Sheets Spreadsheet ID',
        instructions: 'Enter your Google Sheets spreadsheet ID.',
        order: 4,
        placeholder: 'E.g. 4hzvcabRd6yZwux7vK80-NK02zSDD7U-X8MePslAiHvc',
    )]
    protected ?string $googleSheetsId = null;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.googleSheetsId)')]
    #[Input\DynamicSelect(
        label: 'Sheet (optional)',
        instructions: 'Choose the sheet the data should be pushed to. If you leave this field empty, the data will automatically be pushed to the first sheet.',
        order: 5,
        emptyOption: 'First Sheet',
        source: 'api/google-sheets/sheets',
        parameterFields: [
            'id' => 'integrationId',
            'values.googleSheetsId' => 'googleSheetsId',
        ],
    )]
    protected ?string $sheet;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.googleSheetsId)')]
    #[Input\Integer(
        label: 'Row Offset (optional)',
        instructions: "Enter the number of rows to skip from the beginning of the sheet. Input '0' to start from the first row, or '3' to skip the first 3 rows, and so on.",
        order: 5,
        placeholder: '0',
    )]
    protected ?int $offset = null;

    #[Input\Boolean(
        label: 'Process User-inputted Formulas and Formats',
        instructions: 'Any user-inputted values with formula and formatting syntax will be respected and parsed in the spreadsheet. When disabled, these values will be escaped.',
        order: 6,
    )]
    protected bool $processValues = false;

    #[Input\Select(
        label: 'Row Insert Behavior',
        instructions: "Choose how new data rows should be inserted into the Google Sheet. 'Insert New Row' will add a new row to the spreadsheet directly before the first empty row. 'Replace Next Empty Row' will find the first empty row and write the new content into it. Neither option will overwrite existing data.",
        order: 7,
        options: [
            self::INSERT_OPTION_INSERT_ROWS => 'Insert New Row',
            self::INSERT_OPTION_OVERWRITE => 'Replace Next Empty Row',
        ],
    )]
    protected string $insertOption = self::INSERT_OPTION_INSERT_ROWS;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.googleSheetsId)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Google Sheet columns',
        order: 9,
        source: 'api/google-sheets/column-fields',
        parameterFields: [
            'id' => 'integrationId',
            'values.sheet' => 'sheet',
            'values.googleSheetsId' => 'googleSheetsId',
        ],
    )]
    protected ?FieldMapping $fieldMapping = null;

    public function getGoogleSheetsId(): ?string
    {
        return $this->googleSheetsId;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function isProcessValues(): bool
    {
        return $this->processValues;
    }

    public function getInsertOption(): string
    {
        return $this->insertOption;
    }

    public function getSheet(): ?string
    {
        return $this->sheet;
    }

    public function getFieldMapping(): ?FieldMapping
    {
        return $this->fieldMapping;
    }

    public function checkConnection(Client $client): bool
    {
        $url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=';
        $accessToken = $this->getAccessToken();

        $response = $client->get($url.$accessToken);

        $json = json_decode((string) $response->getBody(), false);

        return !empty($json);
    }

    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    public function getApiRootUrl(): string
    {
        return 'https://sheets.googleapis.com';
    }

    protected function getProcessableFields(string $category): array
    {
        $fields = [];
        foreach ($this->fieldMapping as $mapping) {
            $columnIndex = (int) $mapping->getSource();
            $columnLetter = SheetsHelper::getColumnLetter($columnIndex);

            $fields[$columnIndex] = new FieldObject(
                $columnIndex,
                $columnLetter,
                FieldObject::TYPE_STRING,
                $category,
                false,
            );
        }

        return $fields;
    }
}
