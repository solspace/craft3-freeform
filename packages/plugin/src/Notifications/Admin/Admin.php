<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2022, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Notifications\Admin;

use Solspace\Freeform\Attributes\Notification\Type;
use Solspace\Freeform\Attributes\Property\Property;
use Solspace\Freeform\Attributes\Property\PropertyTypes\NotificationTemplates\NotificationTemplateTransformer;
use Solspace\Freeform\Attributes\Property\PropertyTypes\Recipients\RecipientCollection;
use Solspace\Freeform\Attributes\Property\PropertyTypes\Recipients\RecipientTransformer;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Notifications\BaseNotification;

#[Type(
    name: 'Admin Notifications',
    icon: __DIR__.'/icon.svg',
)]
class Admin extends BaseNotification
{
    #[Property(
        label: 'Notification Template',
        type: Property::TYPE_NOTIFICATION_TEMPLATE,
        instructions: 'Select a notification template to use for this notification.',
        order: 9,
        transformer: NotificationTemplateTransformer::class,
    )]
    protected ?NotificationTemplate $template = null;

    #[Property(
        type: Property::TYPE_RECIPIENTS,
        instructions: 'List the recipients of this notification.',
        order: 10,
        value: [],
        transformer: RecipientTransformer::class,
    )]
    protected RecipientCollection $recipients;
}
