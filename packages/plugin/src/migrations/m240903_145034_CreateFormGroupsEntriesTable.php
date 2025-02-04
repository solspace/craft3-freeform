<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;
use Solspace\Freeform\Library\Migrations\ForeignKey;

/**
 * m240903_145034_CreateFormGroupsEntriesTable migration.
 */
class m240903_145034_CreateFormGroupsEntriesTable extends Migration
{
    public function safeUp(): bool
    {
        if ($this->tableExists('{{%freeform_forms_groups_entries}}')) {
            \Craft::warning("Table '{{%freeform_forms_groups}}' already exists. Skipping migration.", __METHOD__);

            return true;
        }

        $this->createTable(
            '{{%freeform_forms_groups_entries}}',
            [
                'id' => $this->primaryKey(),
                'groupId' => $this->integer()->notNull(),
                'formId' => $this->integer()->notNull(),
                'order' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()->notNull(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_forms_groups_entries}}',
            'groupId',
            '{{%freeform_forms_groups}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_forms_groups_entries}}',
            'formId',
            '{{%freeform_forms}}',
            'id',
            ForeignKey::CASCADE
        );

        return true;
    }

    public function safeDown(): bool
    {
        MigrationHelper::dropTableIfExists('{{%freeform_forms_groups_entries}}', $this);

        return true;
    }
}
