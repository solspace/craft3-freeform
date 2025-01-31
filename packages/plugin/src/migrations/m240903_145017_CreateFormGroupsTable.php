<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;
use Solspace\Freeform\Library\Migrations\ForeignKey;

/**
 * m240903_145017_CreateFormGroupsTable migration.
 */
class m240903_145017_CreateFormGroupsTable extends Migration
{
    public function safeUp(): bool
    {
        if ($this->tableExists('{{%freeform_forms_groups}}')) {
            \Craft::warning("Table '{{%freeform_forms_groups}}' already exists. Skipping migration.", __METHOD__);

            return true;
        }

        $this->createTable(
            '{{%freeform_forms_groups}}',
            [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'label' => $this->string()->notNull(),
                'order' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()->notNull(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_forms_groups}}',
            'siteId',
            '{{%sites}}',
            'id',
            ForeignKey::CASCADE
        );

        return true;
    }

    public function safeDown(): bool
    {
        MigrationHelper::dropTableIfExists('{{%freeform_forms_groups}}', $this);

        return true;
    }
}
