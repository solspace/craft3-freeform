<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;
use Solspace\Freeform\Library\Migrations\ForeignKey;

class m220330_111857_SplitSubmissionsTable extends Migration
{
    private static $types = [
        'text',
        'textarea',
        'hidden',
        'select',
        'multiple_select',
        'checkbox',
        'checkbox_group',
        'radio_group',
        'dynamic_recipients',
        'email',
        'file',
        'file_drag_and_drop',
        'datetime',
        'number',
        'phone',
        'website',
        'rating',
        'regex',
        'opinion_scale',
        'signature',
        'table',
        'invisible',
        'cc_details',
    ];

    public function safeUp(): bool
    {
        $forms = (new Query())
            ->select(['id', 'handle', 'layoutJson'])
            ->from('{{%freeform_forms}}')
            ->all()
        ;

        $fields = (new Query())
            ->select(['id', 'handle'])
            ->from('{{%freeform_fields}}')
            ->pairs()
        ;

        $prefix = \Craft::$app->db->tablePrefix;
        $prefixLength = \strlen($prefix);

        $maxHandleSize = 36 - $prefixLength;

        foreach ($forms as $form) {
            $formId = (int) $form['id'];
            $formHandle = $form['handle'];
            $formHandle = StringHelper::toSnakeCase($formHandle);
            $formHandle = StringHelper::truncate($formHandle, $maxHandleSize, '');
            $formHandle = trim($formHandle, '-_');

            $fieldMap = [];
            $layout = json_decode($form['layoutJson']);
            foreach ($layout->composer->properties as $layoutField) {
                $id = $layoutField->id ?? null;
                $type = $layoutField->type ?? null;

                if (!$id || !\in_array($type, self::$types, true)) {
                    continue;
                }

                $handle = $layoutField->handle ?? null;
                if (!$handle || !\array_key_exists($id, $fields)) {
                    continue;
                }

                $handle = StringHelper::toKebabCase($handle, '_');
                $handle = StringHelper::truncate($handle, 50, '');
                $handle = trim($handle, '-_');

                $fieldMap["field_{$id}"] = $handle.'_'.$id;
            }

            $tableName = $this->createFormTable($formId, $formHandle, $fieldMap);
            if ($tableName) {
                $this->swapData($formId, $tableName, $fieldMap);
            }
        }

        $this->cleanUpSubmissionsTable($fields);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m220330_111857_SplitSubmissionsTable cannot be reverted.\n";

        return false;
    }

    private function createFormTable(int $id, string $formHandle, array $fieldMap): ?string
    {
        $tableColumns = ['id' => $this->primaryKey()];
        foreach ($fieldMap as $handle) {
            $tableColumns[$handle] = $this->text();
        }

        $prefix = \Craft::$app->db->tablePrefix;
        $prefixLength = \strlen($prefix);

        $maxHandleSize = 36 - $prefixLength;

        $formHandle = trim(StringHelper::truncate($formHandle, $maxHandleSize, ''), '-_');
        $formHandle = trim($formHandle, '-_');

        $tableName = "{{%freeform_submissions_{$formHandle}_{$id}}}";

        $tableExists = \Craft::$app->db->schema->getTableSchema($tableName);
        if (null === $tableExists) {
            $this->createTable($tableName, $tableColumns);

            $this->addForeignKey(null, $tableName, 'id', '{{%freeform_submissions}}', 'id', ForeignKey::CASCADE);

            return $tableName;
        }

        return null;
    }

    private function swapData(int $formId, string $tableName, array $fieldMap): void
    {
        $submissionQuery = (new Query())
            ->select(['id', ...array_keys($fieldMap)])
            ->from('{{%freeform_submissions}}')
            ->where(['formId' => $formId])
            ->indexBy('id')
        ;

        foreach ($submissionQuery->batch() as $batch) {
            $insertRows = [];
            foreach ($batch as $row) {
                $data = ['id' => $row['id']];
                foreach ($fieldMap as $oldColumn => $newColumn) {
                    $data[$newColumn] = $row[$oldColumn] ?? null;
                }

                $insertRows[] = $data;
            }

            $this->batchInsert(
                $tableName,
                ['id', ...array_values($fieldMap)],
                $insertRows
            );
        }
    }

    private function cleanUpSubmissionsTable(array $fields): void
    {
        foreach ($fields as $id => $handle) {
            $this->dropColumn('{{%freeform_submissions}}', "field_{$id}");
        }
    }
}
