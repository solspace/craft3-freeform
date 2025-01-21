<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;

class m250121_085946_AddForeignKeyToAssetsInUnfinalizedAssets extends Migration
{
    public function safeUp(): bool
    {
        $this->addForeignKey(
            null,
            '{{%freeform_unfinalized_files}}',
            ['assetId'],
            '{{%assets}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        return true;
    }

    public function safeDown(): bool
    {
        $foreignKeys = $this->getDb()->getSchema()->getTableForeignKeys('{{%freeform_unfinalized_files}}');
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->columnNames === ['assetId']) {
                $this->dropForeignKey($foreignKey->name, '{{%freeform_unfinalized_files}}');
            }
        }

        return true;
    }
}
