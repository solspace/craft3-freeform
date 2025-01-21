<?php

namespace Solspace\Freeform\Services;

use craft\helpers\FileHelper;
use craft\web\View;
use Psr\Log\LoggerInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use Solspace\Freeform\Library\Logging\Readers\LineLogReader;

class LoggerService extends BaseService
{
    public function getLogger(string $category, ?string $fileName = null, ?int $level = null): LoggerInterface
    {
        return FreeformLogger::getInstance($category, $fileName, $level);
    }

    public function getLogReader(?string $fileName = null): LineLogReader
    {
        return new LineLogReader(FreeformLogger::getLogfilePath($fileName));
    }

    public function registerJsTranslations(View $view): void
    {
        $view->registerTranslations(Freeform::TRANSLATION_CATEGORY, [
            'Are you sure you want to clear this log?',
        ]);
    }

    public function clearLogs(?string $filePath = null): void
    {
        $logFilePath = FreeformLogger::getLogfilePath($filePath);

        if (file_exists($logFilePath)) {
            FileHelper::unlink($logFilePath);
        }
    }

    public function getColor(string $logger): string
    {
        return FreeformLogger::getColor($logger);
    }
}
