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

namespace Solspace\Freeform\Jobs;

use craft\queue\BaseJob;
use Solspace\Freeform\Freeform;

class ProcessIntegrationsJob extends BaseJob implements IntegrationJobInterface
{
    public ?int $formId = null;

    public array $postedData = [];

    public ?string $type = null;

    public function execute($queue): void
    {
        Freeform::getInstance()
            ->integrations
            ->processIntegrationJob(
                $this->formId,
                $this->postedData,
                $this->type,
            )
        ;
    }

    protected function defaultDescription(): ?string
    {
        return Freeform::t('Freeform: Processing Integrations');
    }
}
