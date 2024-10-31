<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m240819_104209_AddTranslationTable extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable(
            '{{%freeform_forms_translations}}',
            [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'translations' => $this->longText()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->createIndex(
            null,
            '{{freeform_forms_translations}}',
            ['formId', 'siteId'],
            true,
        );

        $this->addForeignKey(
            null,
            '{{freeform_forms_translations}}',
            ['formId'],
            '{{%freeform_forms}}',
            ['id'],
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{freeform_forms_translations}}',
            ['siteId'],
            '{{%sites}}',
            ['id'],
            'CASCADE'
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%freeform_forms_translations}}');

        return true;
    }
}
