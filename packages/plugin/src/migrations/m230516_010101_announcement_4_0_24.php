<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\i18n\Translation;

/**
 * m230516_010101_announcement_4_0_24 migration.
 */
class m230516_010101_announcement_4_0_24 extends Migration
{
    public function safeUp(): bool
    {
        \Craft::$app->announcements->push(
            Translation::prep('freeform', 'Limit form submissions by Email Address', []),
            Translation::prep('freeform', 'You can now prevent users from submitting a form more than once when [using the same email address]({url}) on any Email field.', [
                'url' => 'https://docs.solspace.com/craft/freeform/v4/submissions/submission-limits/',
            ]),
            'freeform',
            true
        );

        return true;
    }

    public function safeDown(): bool
    {
        echo "m230516_010101_announcement_4_0_24 cannot be reverted.\n";

        return false;
    }
}
