<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m241023_080038_AddPdfTemplateTable extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%freeform_pdf_templates}}')) {
            return true;
        }

        $this->createTable(
            '{{%freeform_pdf_templates}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'description' => $this->text(),
                'fileName' => $this->text()->notNull(),
                'body' => $this->longText()->notNull(),
                'sortOrder' => $this->integer()->notNull()->defaultValue(0),
            ]
        );

        $this->addColumn(
            '{{%freeform_notification_templates}}',
            'pdfTemplateIds',
            $this->text()->after('id')
        );

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->tableExists('{{%freeform_pdf_templates}}')) {
            $this->dropTable('{{%freeform_pdf_templates}}');
        }

        return true;
    }
}
