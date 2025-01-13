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

namespace Solspace\Freeform\Library\Integrations\Types\EmailMarketing;

use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\Field\FieldTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Interfaces\BooleanInterface;
use Solspace\Freeform\Fields\Interfaces\RecipientInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\APIIntegration;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;

abstract class EmailMarketingIntegration extends APIIntegration implements EmailMarketingIntegrationInterface
{
    #[Validators\Required]
    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldTransformer::class)]
    #[Input\Field(
        label: 'Target Email Field',
        instructions: 'The email field to be subscribed to the mailing list.',
        order: 1,
        emptyOption: 'Select a field...',
        implements: [RecipientInterface::class],
    )]
    protected ?FieldInterface $emailField = null;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldTransformer::class)]
    #[Input\Field(
        label: 'Opt-in Field (optional)',
        instructions: 'This field has to be checked to push to the mailing list. If unselected, the user will automatically be opted into the mailing list.',
        order: 2,
        emptyOption: 'Automatically opt-in the user',
        implements: [BooleanInterface::class],
    )]
    protected ?FieldInterface $optInField = null;

    #[Validators\Required]
    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(EmailMarketingTransformer::class)]
    #[Input\DynamicSelect(
        instructions: 'The mailing list the user should be subscribed to.',
        order: 3,
        emptyOption: 'Select a mailing list...',
        source: 'api/integrations/email-marketing/lists',
        parameterFields: ['id' => 'id'],
    )]
    protected ?ListObject $mailingList = null;

    public static function isInstallable(): bool
    {
        return true;
    }

    protected function getProcessableFields(string $category): array
    {
        return Freeform::getInstance()->emailMarketing->getFields($this->mailingList, $this, $category);
    }
}
