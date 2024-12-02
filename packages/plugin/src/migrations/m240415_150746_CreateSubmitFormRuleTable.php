<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use Solspace\Freeform\Library\Migrations\ForeignKey;

class m240415_150746_CreateSubmitFormRuleTable extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%freeform_rules_submit_form}}')) {
            return true;
        }

        $this->createTable(
            '{{%freeform_rules_submit_form}}',
            [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_submit_form}}',
            'id',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_submit_form}}',
            'formId',
            '{{%freeform_forms}}',
            'id',
            ForeignKey::CASCADE
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropAllForeignKeysToTable('{{%freeform_rules_submit_form}}');
        $this->dropTableIfExists('{{%freeform_rules_submit_form}}');

        return true;
    }
}
