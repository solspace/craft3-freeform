<?php

namespace Solspace\Freeform\Commands;

use craft\db\Query;
use yii\console\ExitCode;
use yii\helpers\Console;

class FreeformController extends BaseCommand
{
    /**
     * @var string the case you wish to convert handles to, Camel Case or Underscored
     */
    public string $case = 'camelCase';

    public ?bool $dryRun = false;

    public function optionAliases(): array
    {
        return [
            'c' => 'case',
        ];
    }

    public function options($actionID): array
    {
        if ('convert-handles' === $actionID) {
            return [
                'case',
                'dryRun',
            ];
        }

        return [];
    }

    /**
     * Converts handles to camel case or underscores to fix compatibility issues from version 4 to version 5.
     */
    public function actionConvertHandles(): int
    {
        $caseLabel = match ($this->case) {
            'underscores' => 'Underscores',
            default => 'Camel Case',
        };

        $this->stdout("================================================\n", Console::FG_YELLOW);
        $this->stdout("=      Converting Handles to {$caseLabel}      =\n", Console::FG_YELLOW);
        $this->stdout("================================================\n\n", Console::FG_YELLOW);

        if ($this->dryRun) {
            $this->stdout("Dry run enabled. No handles will be saved.\n\n", Console::FG_YELLOW);
        }

        $this->convertFavoriteFields();
        $this->convertForms();
        $this->convertFormsFields();

        $this->stdout("\n\n Done\n", Console::FG_YELLOW);

        return ExitCode::OK;
    }

    private function convertFavoriteFields(): void
    {
        $results = (new Query())
            ->select(['metadata'])
            ->from('{{%freeform_favorite_fields}}')
            ->indexBy('id')
            ->column()
        ;

        foreach ($results as $id => $metadata) {
            $json = json_decode($metadata);

            $originalHandle = $json->handle;
            $newHandle = $this->convertToCase($originalHandle);

            if ($originalHandle !== $newHandle) {
                $json->handle = $newHandle;

                if (!$this->dryRun) {
                    \Craft::$app->getDb()
                        ->createCommand()
                        ->update('{{%freeform_favorite_fields}}', ['metadata' => json_encode($json)], ['id' => $id])
                        ->execute()
                    ;
                }

                $this->stdout("Updated Favorite Field Handle: {$originalHandle} -> {$newHandle}\n");
            }
        }
    }

    private function convertForms(): void
    {
        $results = (new Query())
            ->select(['metadata'])
            ->from('{{%freeform_forms}}')
            ->indexBy('id')
            ->column()
        ;

        foreach ($results as $id => $metadata) {
            $json = json_decode($metadata);

            $originalHandle = $json->general->handle;
            $newHandle = $this->convertToCase($originalHandle);

            if ($originalHandle !== $newHandle) {
                $json->general->handle = $newHandle;

                if (!$this->dryRun) {
                    \Craft::$app->getDb()
                        ->createCommand()
                        ->update('{{%freeform_forms}}', [
                            'handle' => $newHandle,
                            'metadata' => json_encode($json),
                        ], ['id' => $id])
                        ->execute()
                    ;
                }

                $this->stdout("Updated Form Handle: {$originalHandle} -> {$newHandle}\n");
            }
        }
    }

    private function convertFormsFields(): void
    {
        $results = (new Query())
            ->select(['metadata'])
            ->from('{{%freeform_forms_fields}}')
            ->indexBy('id')
            ->column()
        ;

        foreach ($results as $id => $metadata) {
            $json = json_decode($metadata);

            $originalHandle = $json->handle;
            $newHandle = $this->convertToCase($originalHandle);

            if ($originalHandle !== $newHandle) {
                $json->handle = $newHandle;

                if (!$this->dryRun) {
                    \Craft::$app->getDb()
                        ->createCommand()
                        ->update('{{%freeform_forms_fields}}', ['metadata' => json_encode($json)], ['id' => $id])
                        ->execute()
                    ;
                }

                $this->stdout("Updated Form Field Handle: {$originalHandle} -> {$newHandle}\n");
            }
        }
    }

    private function convertToCase(string $handle): string
    {
        return match ($this->case) {
            'underscores' => str_replace(' ', '', str_replace('-', '_', $handle)),
            default => lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $handle)))),
        };
    }
}
