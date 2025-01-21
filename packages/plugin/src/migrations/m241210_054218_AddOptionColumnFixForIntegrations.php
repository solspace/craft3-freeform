<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m241210_054218_AddOptionColumnFixForIntegrations extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->columnExists('{{%freeform_crm_fields}}', 'options')) {
            return true;
        }

        $this->addColumn(
            '{{%freeform_crm_fields}}',
            'options',
            $this->longText()->after('required')
        );

        $this->addColumn(
            '{{%freeform_email_marketing_fields}}',
            'options',
            $this->longText()->after('required')
        );

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->columnExists('{{%freeform_crm_fields}}', 'options')) {
            return true;
        }

        $this->dropColumn('{{%freeform_crm_fields}}', 'options');
        $this->dropColumn('{{%freeform_email_marketing_fields}}', 'options');

        return true;
    }
}
