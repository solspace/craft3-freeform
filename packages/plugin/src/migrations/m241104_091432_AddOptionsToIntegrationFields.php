<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m241104_091432_AddOptionsToIntegrationFields extends Migration
{
    public function safeUp(): bool
    {
        $this->addColumn(
            '{{%freeform_crm_fields}}',
            'options',
            $this->json()->after('required')
        );

        $this->addColumn(
            '{{%freeform_email_marketing_fields}}',
            'options',
            $this->json()->after('required')
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropColumn('{{%freeform_crm_fields}}', 'options');
        $this->dropColumn('{{%freeform_email_marketing_fields}}', 'options');

        return true;
    }
}
