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
use Solspace\Freeform\Notifications\BaseNotification;

#[Type(
    name: 'Admin Notifications',
    icon: __DIR__.'/icon.svg',
)]
class Admin extends BaseNotification
{
}
