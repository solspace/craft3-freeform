<?php

namespace Solspace\Freeform\Integrations\CRM\Insightly\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Delimiter;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\CRM\Insightly\BaseInsightlyIntegration;

#[Type(
    name: 'Insightly',
    type: Type::TYPE_CRM,
    version: 'v3.1',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class InsightlyV31 extends BaseInsightlyIntegration
{
    protected const API_VERSION = 'v3.1';

    // ==========================================
    //                Leads
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Leads')]
    #[Input\Boolean(
        label: 'Map to Leads',
        instructions: 'Should map to the Leads endpoint.',
        order: 2,
    )]
    protected bool $mapLeads = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapLeads)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Insightly Leads fields.',
        order: 3,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_LEAD,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $leadMapping = null;

    public function getApiRootUrl(): string
    {
        $url = $this->getApiUrl();

        $url = rtrim($url, '/');

        return $url.'/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        $this->processLeads($form, $client);
    }

    private function processLeads(Form $form, Client $client): void
    {
        if (!$this->mapLeads) {
            $this->logger->debug('No Leads mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->leadMapping, self::CATEGORY_LEAD);
        if (!$mapping) {
            return;
        }

        $response = $client->post(
            $this->getEndpoint('/Leads'),
            ['json' => $mapping],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_LEAD, $response);
    }
}
