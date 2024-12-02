<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use Solspace\Freeform\Library\Migrations\ForeignKey;

/**
 * m240425_062916_CreateButtonRuleTable migration.
 */
class m240425_062916_CreateButtonRuleTable extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%freeform_rules_buttons}}')) {
            return true;
        }

        $this->createTable(
            '{{%freeform_rules_buttons}}',
            [
                'id' => $this->primaryKey(),
                'pageId' => $this->integer()->notNull(),
                'button' => $this->string(10)->notNull(),
                'display' => $this->string(10)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_buttons}}',
            'id',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_buttons}}',
            'pageId',
            '{{%freeform_forms_pages}}',
            'id',
            ForeignKey::CASCADE,
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropAllForeignKeysToTable('{{%freeform_rules_buttons}}');
        $this->dropTableIfExists('{{%freeform_rules_buttons}}');

        return true;
    }
}
