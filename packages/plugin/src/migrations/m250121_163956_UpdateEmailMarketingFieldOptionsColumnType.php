<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\db\Query;

class m250121_163956_UpdateEmailMarketingFieldOptionsColumnType extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%freeform_email_marketing_fields}}', 'options')) {
            return true;
        }

        $this->alterColumn(
            '{{%freeform_email_marketing_fields}}',
            'options',
            $this->longText()
        );

        $results = (new Query())
            ->select(['options'])
            ->from('{{%freeform_email_marketing_fields}}')
            ->indexBy('id')
            ->column()
        ;

        foreach ($results as $id => $options) {
            $this->update(
                '{{%freeform_email_marketing_fields}}',
                ['options' => json_encode($options)],
                ['id' => $id],
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->alterColumn(
            '{{%freeform_email_marketing_fields}}',
            'options',
            $this->json()
        );

        return true;
    }
}
