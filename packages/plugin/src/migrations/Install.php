<?php

namespace Solspace\Freeform\migrations;

use Solspace\Freeform\Library\Migrations\ForeignKey;
use Solspace\Freeform\Library\Migrations\StreamlinedInstallMigration;
use Solspace\Freeform\Library\Migrations\Table;

/**
 * Install migration.
 */
class Install extends StreamlinedInstallMigration
{
    protected function defineTableData(): array
    {
        return [
            (new Table('freeform_forms'))
                ->addField('id', $this->primaryKey())
                ->addField('type', $this->string(200)->notNull())
                ->addField('name', $this->string(100)->notNull())
                ->addField('handle', $this->string(100)->notNull()->unique())
                ->addField('spamBlockCount', $this->integer()->unsigned()->notNull()->defaultValue(0))
                ->addField('metadata', $this->longText())
                ->addField('order', $this->integer())
                ->addIndex(['order'])
                ->addField('createdByUserId', $this->integer())
                ->addField('updatedByUserId', $this->integer())
                ->addForeignKey(
                    'createdByUserId',
                    'users',
                    'id',
                    ForeignKey::SET_NULL,
                    ForeignKey::CASCADE
                )
                ->addForeignKey(
                    'updatedByUserId',
                    'users',
                    'id',
                    ForeignKey::SET_NULL,
                    ForeignKey::CASCADE
                )
                ->addField('dateArchived', $this->dateTime()->null()),

            (new Table('freeform_forms_sites'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('siteId', $this->integer()->notNull())
                ->addIndex(['siteId', 'formId'], true)
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('siteId', 'sites', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_layouts'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addIndex(['formId'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_pages'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('layoutId', $this->integer()->notNull())
                ->addField('label', $this->string(255)->notNull())
                ->addField('order', $this->integer())
                ->addField('metadata', $this->longText())
                ->addIndex(['formId', 'order'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('layoutId', 'freeform_forms_layouts', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_rows'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('layoutId', $this->integer()->notNull())
                ->addField('order', $this->integer())
                ->addIndex(['formId', 'order'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('layoutId', 'freeform_forms_layouts', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_fields'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('type', $this->string(255)->notNull())
                ->addField('metadata', $this->longText())
                ->addField('rowId', $this->integer())
                ->addField('order', $this->integer())
                ->addIndex(['rowId', 'order'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('rowId', 'freeform_forms_rows', 'id', ForeignKey::SET_NULL),

            (new Table('freeform_forms_integrations'))
                ->addField('id', $this->primaryKey())
                ->addField('integrationId', $this->integer()->notNull())
                ->addField('formId', $this->integer()->notNull())
                ->addField('enabled', $this->boolean()->notNull()->defaultValue(true))
                ->addField('metadata', $this->longText())
                ->addForeignKey('integrationId', 'freeform_integrations', 'id', ForeignKey::CASCADE)
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_notifications'))
                ->addField('id', $this->primaryKey())
                ->addField('class', $this->string(255)->notNull())
                ->addField('formId', $this->integer()->notNull())
                ->addField('enabled', $this->boolean()->notNull()->defaultValue(true))
                ->addField('metadata', $this->longText())
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_favorite_fields'))
                ->addField('id', $this->primaryKey())
                ->addField('userId', $this->integer())
                ->addField('label', $this->string(255)->notNull())
                ->addField('type', $this->string(255)->notNull())
                ->addField('metadata', $this->longText())
                ->addForeignKey('userId', 'users', 'id', ForeignKey::CASCADE),

            (new Table('freeform_notification_templates'))
                ->addField('id', $this->primaryKey())
                ->addField('pdfTemplateIds', $this->text())
                ->addField('name', $this->string(255)->notNull())
                ->addField('handle', $this->string(255)->notNull()->unique())
                ->addField('subject', $this->string(255)->notNull())
                ->addField('description', $this->text())
                ->addField('fromName', $this->string(255)->notNull())
                ->addField('fromEmail', $this->string(255)->notNull())
                ->addField('replyToName', $this->string(255))
                ->addField('replyToEmail', $this->string(255))
                ->addField('cc', $this->string(255))
                ->addField('bcc', $this->string(255))
                ->addField('bodyHtml', $this->mediumText())
                ->addField('bodyText', $this->mediumText())
                ->addField('autoText', $this->boolean()->notNull()->defaultValue(true))
                ->addField('includeAttachments', $this->boolean()->defaultValue(true))
                ->addField('presetAssets', $this->string(255))
                ->addField('sortOrder', $this->integer()),

            (new Table('freeform_integrations'))
                ->addField('id', $this->primaryKey())
                ->addField('enabled', $this->boolean()->defaultValue(true))
                ->addField('name', $this->string(255)->notNull())
                ->addField('handle', $this->string(255)->notNull()->unique())
                ->addField('type', $this->string(50)->notNull())
                ->addField('class', $this->string(255))
                ->addField('metadata', $this->longText())
                ->addIndex(['type']),

            (new Table('freeform_email_marketing_lists'))
                ->addField('id', $this->primaryKey())
                ->addField('integrationId', $this->integer()->notNull())
                ->addField('resourceId', $this->string(255)->notNull())
                ->addField('name', $this->string(255)->notNull())
                ->addField('memberCount', $this->integer())
                ->addIndex(['integrationId', 'resourceId'], true)
                ->addForeignKey('integrationId', 'freeform_integrations', 'id', ForeignKey::CASCADE),

            (new Table('freeform_email_marketing_fields'))
                ->addField('id', $this->primaryKey())
                ->addField('mailingListId', $this->integer()->notNull())
                ->addField('label', $this->string(255)->notNull())
                ->addField('handle', $this->string(255)->notNull())
                ->addField('type', $this->string(50)->notNull())
                ->addField('category', $this->string(50))
                ->addField('required', $this->boolean()->defaultValue(false))
                ->addField('options', $this->longText())
                ->addIndex(['type'])
                ->addIndex(['mailingListId', 'category'])
                ->addForeignKey('mailingListId', 'freeform_email_marketing_lists', 'id', ForeignKey::CASCADE),

            (new Table('freeform_crm_fields'))
                ->addField('id', $this->primaryKey())
                ->addField('integrationId', $this->integer()->notNull())
                ->addField('label', $this->text()->notNull())
                ->addField('handle', $this->string(255)->notNull())
                ->addField('type', $this->string(50)->notNull())
                ->addField('category', $this->string(50))
                ->addField('required', $this->boolean()->defaultValue(false))
                ->addField('options', $this->longText())
                ->addIndex(['type'])
                ->addIndex(['integrationId', 'category'])
                ->addForeignKey('integrationId', 'freeform_integrations', 'id', ForeignKey::CASCADE),

            (new Table('freeform_statuses'))
                ->addField('id', $this->primaryKey())
                ->addField('name', $this->string(255)->notNull())
                ->addField('handle', $this->string(255)->notNull()->unique())
                ->addField('color', $this->string(30))
                ->addField('sortOrder', $this->integer()),

            (new Table('freeform_unfinalized_files'))
                ->addField('id', $this->primaryKey())
                ->addField('assetId', $this->integer()->notNull())
                ->addField('fieldHandle', $this->string(255))
                ->addField('formToken', $this->string(255))
                ->addForeignKey('assetId', 'assets', 'id', ForeignKey::CASCADE)
                ->addIndex(['fieldHandle', 'formToken']),

            (new Table('freeform_submissions'))
                ->addField('id', $this->primaryKey())
                ->addField('incrementalId', $this->integer()->notNull())
                ->addField('userId', $this->integer())
                ->addField('statusId', $this->integer())
                ->addField('formId', $this->integer()->notNull())
                ->addField('token', $this->string(100)->notNull())
                ->addField('ip', $this->string(46)->null())
                ->addField('isSpam', $this->boolean()->defaultValue(false))
                ->addField('isHidden', $this->boolean()->defaultValue(false))
                ->addField('requestId', $this->string(255)->null())
                ->addIndex(['incrementalId'], true)
                ->addIndex(['token'], true)
                ->addForeignKey('id', 'elements', 'id', ForeignKey::CASCADE)
                ->addForeignKey('userId', 'users', 'id', ForeignKey::CASCADE)
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('statusId', 'freeform_statuses', 'id', ForeignKey::CASCADE),

            (new Table('freeform_integrations_queue'))
                ->addField('id', $this->primaryKey())
                ->addField('submissionId', $this->integer()->notNull())
                ->addField('integrationType', $this->string(50)->notNull())
                ->addField('status', $this->string(50)->notNull())
                ->addField('fieldHash', $this->string(20))
                ->addIndex(['status'], false)
                ->addForeignKey('submissionId', 'freeform_submissions', 'id', ForeignKey::CASCADE),

            // Pro
            (new Table('freeform_export_profiles'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('name', $this->string(255)->notNull()->unique())
                ->addField('limit', $this->integer())
                ->addField('dateRange', $this->string(255))
                ->addField('rangeStart', $this->string(255)->null())
                ->addField('rangeEnd', $this->string(255)->null())
                ->addField('fields', $this->longText()->notNull())
                ->addField('filters', $this->longText())
                ->addField('statuses', $this->text()->notNull())
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_export_notifications'))
                ->addField('id', $this->primaryKey())
                ->addField('profileId', $this->integer()->notNull())
                ->addField('name', $this->string(255)->notNull()->unique())
                ->addField('fileType', $this->string(30)->notNull())
                ->addField('fileName', $this->string(255))
                ->addField('frequency', $this->string(20)->notNull())
                ->addField('recipients', $this->longText()->notNull())
                ->addField('subject', $this->string(255))
                ->addField('message', $this->text())
                ->addForeignKey('profileId', 'freeform_export_profiles', 'id', ForeignKey::CASCADE),

            (new Table('freeform_export_settings'))
                ->addField('id', $this->primaryKey())
                ->addField('userId', $this->integer()->notNull())
                ->addField('setting', $this->longText())
                ->addForeignKey('userId', 'users', 'id', ForeignKey::CASCADE),

            // Payments
            (new Table('freeform_payments'))
                ->addField('id', $this->primaryKey())
                ->addField('integrationId', $this->integer()->notNull())
                ->addField('fieldId', $this->integer()->notNull())
                ->addField('submissionId', $this->integer()->notNull())
                ->addField('resourceId', $this->string(50))
                ->addField('type', $this->string(20))
                ->addField('amount', $this->float(2))
                ->addField('currency', $this->string(3))
                ->addField('status', $this->string(40))
                ->addField('link', $this->string(255)->null())
                ->addField('metadata', $this->longText())
                ->addForeignKey('fieldId', 'freeform_forms_fields', 'id', ForeignKey::CASCADE)
                ->addForeignKey('submissionId', 'freeform_submissions', 'id', ForeignKey::CASCADE)
                ->addForeignKey('integrationId', 'freeform_integrations', 'id', ForeignKey::CASCADE)
                ->addIndex(['integrationId', 'resourceId'], true)
                ->addIndex(['integrationId', 'type'])
                ->addIndex(['resourceId']),

            (new Table('freeform_submission_notes'))
                ->addField('id', $this->primaryKey())
                ->addField('submissionId', $this->integer()->notNull())
                ->addField('note', $this->text())
                ->addForeignKey('submissionId', 'freeform_submissions', 'id', ForeignKey::CASCADE),

            (new Table('freeform_spam_reason'))
                ->addField('id', $this->primaryKey())
                ->addField('submissionId', $this->integer()->notNull())
                ->addField('reasonType', $this->string(100)->notNull())
                ->addField('reasonMessage', $this->text())
                ->addIndex(['submissionId', 'reasonType'])
                ->addForeignKey('submissionId', 'freeform_submissions', 'id', ForeignKey::CASCADE),

            (new Table('freeform_feeds'))
                ->addField('id', $this->primaryKey())
                ->addField('hash', $this->string()->notNull())
                ->addField('min', $this->string())
                ->addField('max', $this->string())
                ->addField('issueDate', $this->dateTime()->notNull())
                ->addIndex(['hash'], true),

            (new Table('freeform_feed_messages'))
                ->addField('id', $this->primaryKey())
                ->addField('feedId', $this->integer()->notNull())
                ->addField('message', $this->text()->notNull())
                ->addField('conditions', $this->longText()->notNull())
                ->addField('type', $this->string()->notNull())
                ->addField('seen', $this->boolean()->notNull()->defaultValue(false))
                ->addField('issueDate', $this->dateTime()->notNull())
                ->addForeignKey('feedId', 'freeform_feeds', 'id', ForeignKey::CASCADE),

            (new Table('freeform_notification_log'))
                ->addField('id', $this->primaryKey())
                ->addField('type', $this->string(30)->notNull())
                ->addField('name', $this->string())
                ->addIndex(['type', 'dateCreated']),

            (new Table('freeform_session_context'))
                ->addField('id', $this->primaryKey())
                ->addField('contextKey', $this->string(100)->notNull())
                ->addField('sessionId', $this->string(100)->notNull())
                ->addField('formId', $this->integer()->notNull())
                ->addField('propertyBag', $this->longText())
                ->addField('attributeBag', $this->longText())
                ->addIndex(['contextKey', 'formId'])
                ->addIndex(['sessionId'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_saved_forms'))
                ->addField('id', $this->primaryKey())
                ->addField('sessionId', $this->string(100))
                ->addField('formId', $this->integer()->notNull())
                ->addField('token', $this->string(100)->notNull())
                ->addField('payload', $this->longText())
                ->addIndex(['token'])
                ->addIndex(['dateCreated'])
                ->addIndex(['sessionId'])
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_rules'))
                ->addField('id', $this->primaryKey())
                ->addField('combinator', $this->string(20)->notNull()),

            (new Table('freeform_rules_fields'))
                ->addField('id', $this->primaryKey())
                ->addField('fieldId', $this->integer()->notNull())
                ->addField('display', $this->string(10)->notNull()),

            (new Table('freeform_rules_pages'))
                ->addField('id', $this->primaryKey())
                ->addField('pageId', $this->integer()->notNull()),

            (new Table('freeform_rules_notifications'))
                ->addField('id', $this->primaryKey())
                ->addField('notificationId', $this->integer()->notNull())
                ->addField('send', $this->boolean()->notNull()),

            (new Table('freeform_rules_submit_form'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull()),

            (new Table('freeform_rules_buttons'))
                ->addField('id', $this->primaryKey())
                ->addField('pageId', $this->integer()->notNull())
                ->addField('button', $this->string(30)->notNull())
                ->addField('display', $this->string(10)->notNull()),

            (new Table('freeform_rules_conditions'))
                ->addField('id', $this->primaryKey())
                ->addField('ruleId', $this->integer()->notNull())
                ->addField('fieldId', $this->integer()->notNull())
                ->addField('operator', $this->string(20)->notNull())
                ->addField('value', $this->text()->notNull())
                ->addForeignKey(
                    'ruleId',
                    'freeform_rules',
                    'id',
                    ForeignKey::CASCADE,
                    ForeignKey::CASCADE
                ),

            (new Table('freeform_fields_type_groups'))
                ->addField('id', $this->primaryKey())
                ->addField('color', $this->string(10))
                ->addField('label', $this->string())
                ->addField('types', $this->longText()->notNull()),

            (new Table('freeform_limited_users'))
                ->addField('id', $this->primaryKey())
                ->addField('name', $this->string()->notNull())
                ->addField('description', $this->text()->notNull())
                ->addField('settings', $this->longText()->notNull()),

            (new Table('freeform_survey_preferences'))
                ->addField('id', $this->primaryKey())
                ->addField('userId', $this->integer()->notNull())
                ->addField('fieldId', $this->integer()->notNull())
                ->addField('chartType', $this->string(200)->notNull())
                ->addForeignKey('userId', 'users', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_groups'))
                ->addField('id', $this->primaryKey())
                ->addField('siteId', $this->integer()->notNull())
                ->addField('label', $this->string())
                ->addField('order', $this->integer()->notNull())
                ->addForeignKey('siteId', 'sites', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_groups_entries'))
                ->addField('id', $this->primaryKey())
                ->addField('groupId', $this->integer()->notNull())
                ->addField('formId', $this->integer()->notNull())
                ->addField('order', $this->integer()->notNull())
                ->addForeignKey('groupId', 'freeform_forms_groups', 'id', ForeignKey::CASCADE)
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE),

            (new Table('freeform_forms_translations'))
                ->addField('id', $this->primaryKey())
                ->addField('formId', $this->integer()->notNull())
                ->addField('siteId', $this->integer()->notNull())
                ->addField('translations', $this->longText()->notNull())
                ->addIndex(['formId', 'siteId'], true)
                ->addForeignKey('formId', 'freeform_forms', 'id', ForeignKey::CASCADE)
                ->addForeignKey('siteId', 'sites', 'id', ForeignKey::CASCADE),

            (new Table('freeform_pdf_templates'))
                ->addField('id', $this->primaryKey())
                ->addField('name', $this->string(255)->notNull())
                ->addField('description', $this->text())
                ->addField('fileName', $this->text()->notNull())
                ->addField('body', $this->longText()->notNull())
                ->addField('sortOrder', $this->integer()->notNull()->defaultValue(0)),
        ];
    }

    protected function afterInstall(): bool
    {
        $this->addForeignKey(null, '{{%freeform_rules_fields}}', ['id'], '{{%freeform_rules}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_fields}}', ['fieldId'], '{{%freeform_forms_fields}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_pages}}', ['id'], '{{%freeform_rules}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_pages}}', ['pageId'], '{{%freeform_forms_pages}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_notifications}}', ['id'], '{{%freeform_rules}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_notifications}}', ['notificationId'], '{{%freeform_forms_notifications}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_submit_form}}', ['id'], '{{%freeform_rules}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_submit_form}}', ['formId'], '{{%freeform_forms}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_buttons}}', ['id'], '{{%freeform_rules}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_buttons}}', ['pageId'], '{{%freeform_forms_pages}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_rules_conditions}}', ['fieldId'], '{{%freeform_forms_fields}}', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE);
        $this->addForeignKey(null, '{{%freeform_survey_preferences}}', ['fieldId'], '{{%freeform_forms_fields}}', ['id'], ForeignKey::CASCADE);

        return parent::afterInstall();
    }
}
