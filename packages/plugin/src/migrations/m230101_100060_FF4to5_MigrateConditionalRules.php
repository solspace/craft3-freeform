<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use Solspace\Freeform\Library\Migrations\ForeignKey;

class m230101_100060_FF4to5_MigrateConditionalRules extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable(
            '{{%freeform_rules}}',
            [
                'id' => $this->primaryKey(),
                'combinator' => $this->string(20)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        // ------------------------------------------------------------------

        $this->createTable(
            '{{%freeform_rules_fields}}',
            [
                'id' => $this->primaryKey(),
                'fieldId' => $this->integer()->notNull(),
                'display' => $this->string(10)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_fields}}',
            'id',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_fields}}',
            'fieldId',
            '{{%freeform_forms_fields}}',
            'id',
            ForeignKey::CASCADE
        );

        // ------------------------------------------------------------------

        $this->createTable(
            '{{%freeform_rules_pages}}',
            [
                'id' => $this->primaryKey(),
                'pageId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_pages}}',
            'id',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_pages}}',
            'pageId',
            '{{%freeform_forms_pages}}',
            'id',
            ForeignKey::CASCADE
        );

        // ------------------------------------------------------------------

        $this->createTable(
            '{{%freeform_rules_notifications}}',
            [
                'id' => $this->primaryKey(),
                'notificationId' => $this->integer()->notNull(),
                'send' => $this->boolean()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_notifications}}',
            'id',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_notifications}}',
            'notificationId',
            '{{%freeform_forms_notifications}}',
            'id',
            ForeignKey::CASCADE
        );

        // ------------------------------------------------------------------

        $this->createTable(
            '{{%freeform_rules_conditions}}',
            [
                'id' => $this->primaryKey(),
                'ruleId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'operator' => $this->string(20)->notNull(),
                'value' => $this->text()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_conditions}}',
            'ruleId',
            '{{%freeform_rules}}',
            'id',
            ForeignKey::CASCADE
        );

        $this->addForeignKey(
            null,
            '{{%freeform_rules_conditions}}',
            'fieldId',
            '{{%freeform_forms_fields}}',
            'id',
            ForeignKey::CASCADE
        );

        // ------------------------------------------------------------------

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

        // ------------------------------------------------------------------

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
        echo "m230101_100060_FF4to5_MigrateConditionalRules cannot be reverted.\n";

        return false;
    }
}
