<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\i18n\Translation;

/**
 * m230424_010101_announcement_4_0_23 migration.
 */
class m230424_010101_announcement_4_0_23 extends Migration
{
    public function safeUp(): bool
    {
        \Craft::$app->announcements->push(
            Translation::prep('freeform', 'New Floating Labels formatting template', []),
            Translation::prep('freeform', 'The [Floating Labels]({url}) formatting template is ready-to-go and requires no frameworks or toolkits.', [
                'url' => 'https://docs.solspace.com/craft/freeform/v4/templates/formatting/basic-floating-labels/',
            ]),
            'freeform',
            true
        );

        \Craft::$app->announcements->push(
            Translation::prep('freeform', 'New Demo Templates area!', []),
            Translation::prep('freeform', 'Completely revamped [demo templates]({url})! Quickly try on a wide range of sample formatting templates on your forms, view submission data, check out advanced setups and more.', [
                'url' => 'https://docs.solspace.com/craft/freeform/v4/configuration/demo-templates/',
            ]),
            'freeform',
            true
        );

        \Craft::$app->announcements->push(
            Translation::prep('freeform', 'Parse Twig inside Predefined Assets feature', []),
            Translation::prep('freeform', 'Set [Predefined Assets]({url}) dynamically from a field value, allowing the user\'s selection to determine which asset(s) to include in the email notification.', [
                'url' => 'https://docs.solspace.com/craft/freeform/v4/forms/email-notifications/#template-options',
            ]),
            'freeform',
            true
        );
        \Craft::$app->announcements->push(
            Translation::prep('freeform', 'Map to Craft Entry postDate or expiryDate', []),
            Translation::prep('freeform', 'When using the [Element Connections]({url}) feature, you can now map Freeform fields to Craft Entry "postDate" and "expiryDate".', [
                'url' => 'https://docs.solspace.com/craft/freeform/v4/integrations/elements/',
            ]),
            'freeform',
            true
        );

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230424_010101_announcement_4_0_23 cannot be reverted.\n";

        return false;
    }
}
