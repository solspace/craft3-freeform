<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m241206_123733_AddIsHiddenToSubmissions extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%freeform_submissions}}', 'isHidden')) {
            $this->addColumn(
                '{{%freeform_submissions}}',
                'isHidden',
                $this->boolean()->notNull()->defaultValue(false)
            );

            $this->addColumn(
                '{{%freeform_submissions}}',
                'requestId',
                $this->string(255)->null()
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%freeform_submissions}}', 'isHidden')) {
            $this->dropColumn('{{%freeform_submissions}}', 'isHidden');
            $this->dropColumn('{{%freeform_submissions}}', 'requestId');
        }

        return true;
    }
}
