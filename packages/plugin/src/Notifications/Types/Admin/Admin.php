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

namespace Solspace\Freeform\Notifications\Types\Admin;

use Solspace\Freeform\Attributes\Notification\Type;
use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Implementations\Notifications\NotificationTemplates\NotificationTemplateTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\Notifications\Recipients\RecipientTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Notifications\BaseNotification;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;

#[Type(
    name: 'Admin',
    newInstanceName: 'Admin',
    order: 1,
    icon: __DIR__.'/../Icons/admin.svg',
)]
class Admin extends BaseNotification
{
    #[ValueTransformer(NotificationTemplateTransformer::class)]
    #[DefaultValue('notifications.admin.template')]
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

    public function getTemplate(): ?NotificationTemplate
    {
        return $this->template;
    }

    public function getRecipients(): RecipientCollection
    {
        return $this->recipients;
    }
}
