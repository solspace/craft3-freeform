<?php

namespace Solspace\Freeform\migrations;

use craft\db\Migration;
use craft\db\Query;

class m241126_113656_UpdateTableLayoutProperties extends Migration
{
    public function safeUp(): bool
    {
        $rows = (new Query())
            ->select(['metadata'])
            ->from('{{%freeform_forms_fields}}')
            ->where(['type' => 'Solspace\Freeform\Fields\Implementations\Pro\TableField'])
            ->indexBy('id')
            ->column()
        ;

        foreach ($rows as $id => $metadata) {
            $json = json_decode($metadata, true);
            if (empty($json)) {
                continue;
            }

            $tableLayout = $json['tableLayout'] ?? [];
            foreach ($tableLayout as $index => $row) {
                if (\array_key_exists('options', $json)) {
                    continue;
                }

                if ('select' === $row['type']) {
                    $tableLayout[$index]['options'] = explode(';', $row['value']);
                    $tableLayout[$index]['value'] = $tableLayout[$index]['options'][0] ?? '';
                } else {
                    $tableLayout[$index]['options'] = [];
                }

                if ('checkbox' === $row['type']) {
                    $tableLayout[$index]['value'] = (bool) $row['value'];
                }

                $tableLayout[$index]['placeholder'] = '';

                if ('checkbox' === $row['type']) {
                    $tableLayout[$index]['checked'] = (bool) $row['value'];
                } else {
                    $tableLayout[$index]['checked'] = false;
                }

                $json['tableLayout'] = $tableLayout;
            }

            $this->update(
                '{{%freeform_forms_fields}}',
                ['metadata' => json_encode($json)],
                ['id' => $id],
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
