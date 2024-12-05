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

namespace Solspace\Freeform\Notifications\Types\Conditional;

use Solspace\Freeform\Attributes\Notification\Type;
use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Implementations\Notifications\NotificationTemplates\NotificationTemplateTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\Notifications\Recipients\RecipientTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Library\Rules\Types\NotificationRule;
use Solspace\Freeform\Notifications\BaseNotification;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;

#[Type(
    name: 'Conditional',
    newInstanceName: 'Conditional',
    order: 2,
    icon: __DIR__.'/../Icons/conditional.svg',
)]
class Conditional extends BaseNotification
{
    #[ValueTransformer(NotificationTemplateTransformer::class)]
    #[DefaultValue('notifications.conditional.template')]
    #[Input\NotificationTemplate(
        label: 'Notification Template',
        instructions: 'Select an email notification template to use for this notification.',
        order: 3,
    )]
    protected ?NotificationTemplate $template;

    #[ValueTransformer(RecipientTransformer::class)]
    #[Input\Recipients(
        instructions: 'List the recipients of this notification.',
        order: 4,
        value: [],
    )]
    protected RecipientCollection $recipients;

    #[ValueTransformer(NotificationRuleTransformer::class)]
    #[Input\Special\ConditionalNotificationRule(
        label: 'Notification Rule',
        instructions: 'Select a rule to use for this notification.',
        order: 5,
    )]
    protected ?NotificationRule $rule;

    public function getTemplate(): ?NotificationTemplate
    {
        return $this->template;
    }

    public function getRecipients(): RecipientCollection
    {
        return $this->recipients;
    }

    public function getRule(): ?NotificationRule
    {
        return $this->rule;
    }
}
