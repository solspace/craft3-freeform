<?php

namespace Solspace\Freeform\Integrations\CRM\Pardot\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\CRM\Pardot\BasePardotIntegration;

#[Type(
    name: 'Pardot',
    type: Type::TYPE_CRM,
    version: 'v4',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class PardotV4 extends BasePardotIntegration
{
    protected const API_VERSION = '4';

    // ==========================================
    //                 Prospect
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Input\Boolean(
        label: 'Map to Prospect',
        instructions: 'Should map to the Prospect endpoint.',
        order: 5,
    )]
    protected bool $mapProspect = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapProspect)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pardot Prospect fields',
        order: 6,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_PROSPECT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $prospectMapping = null;

    // ==========================================
    //                   Custom
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Input\Boolean(
        label: 'Map to Custom',
        instructions: 'Should map to the Custom endpoint.',
        order: 7,
    )]
    protected bool $mapCustom = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapCustom)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pardot Custom fields',
        order: 8,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CUSTOM,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $customMapping = null;

    public function getApiRootUrl(): string
    {
        return 'https://pi.pardot.com/api';
    }

    public function push(Form $form, Client $client): void
    {
        $this->processProspect($form, $client);
    }

    protected function getPardotEndpoint(string $object = 'prospect', string $action = 'query'): string
    {
        $root = rtrim($this->getApiRootUrl(), '/');
        $object = trim($object, '/');
        $action = ltrim($action, '/');

        return $root.'/'.$object.'/version/'.self::API_VERSION.'/do/'.$action;
    }

    private function processProspect(Form $form, Client $client): void
    {
        $prospectMapping = [];

        $customMapping = [];

        if ($this->mapProspect) {
            $prospectMapping = $this->processMapping($form, $this->prospectMapping, self::CATEGORY_PROSPECT);
        }

        if ($this->mapCustom) {
            $customMapping = $this->processMapping($form, $this->customMapping, self::CATEGORY_CUSTOM);
        }

        $mapping = array_merge($prospectMapping, $customMapping);
        if (!$mapping) {
            $this->logger->debug('No data mapped, skipping.');

            return;
        }

        $email = $mapping['email'];
        unset($mapping['email']);

        [$response, $json] = $this->getJsonResponse($client->post(
            $this->getPardotEndpoint('prospect', 'create/email/'.$email),
            ['query' => $mapping],
        ));

        $this->logger->info('New Prospect created', ['id' => $json?->id ?? $json?->Id ?? null]);

        $this->triggerAfterResponseEvent(self::CATEGORY_PROSPECT, $response);
    }
}
