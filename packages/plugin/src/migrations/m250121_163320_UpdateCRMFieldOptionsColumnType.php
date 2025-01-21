<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m250121_163320_UpdateCRMFieldOptionsColumnType extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%freeform_crm_fields}}', 'options')) {
            return true;
        }

        $this->alterColumn(
            '{{%freeform_crm_fields}}',
            'options',
            $this->longText()
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->alterColumn(
            '{{%freeform_crm_fields}}',
            'options',
            $this->json()
        );

        return true;
    }
}
