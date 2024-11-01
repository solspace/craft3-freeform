<?php

namespace Solspace\Freeform\Bundles\Backup\Controllers;

use craft\helpers\App;
use JetBrains\PhpStorm\NoReturn;
use Solspace\Freeform\Bundles\Backup\DTO\FreeformDataset;
use Solspace\Freeform\Bundles\Backup\Export\ExpressFormsExporter;
use Solspace\Freeform\Bundles\Backup\Export\FreeformFormsExporter;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Library\Helpers\CryptoHelper;
use Solspace\Freeform\Library\ServerSentEvents\SSE;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ExportController extends BaseApiController
{
    private array $unlinkFiles = [];

    public function actionFreeform(): Response
    {
        $exporter = \Craft::$container->get(FreeformFormsExporter::class);

        return $this->asSerializedJson(
            $exporter->collectDataPreview(),
            context: ['preserve_empty_objects' => false],
        );
    }

    public function actionExportInit(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $post = \Craft::$app->request->post();
        $token = CryptoHelper::getUniqueToken(14);

        \Craft::$app->cache->set(
            "freeform-export-{$token}",
            $post,
            30
        );

        return $this->asSerializedJson([
            'token' => $token,
        ], 201);
    }

    public function actionDownload(): Response
    {
        $token = $this->request->get('token');
        $file = \Craft::$app->cache->get("freeform-export-file-{$token}");

        if (!$file) {
            throw new NotFoundHttpException('Invalid token');
        }

        if (!file_exists($file)) {
            throw new NotFoundHttpException('File does not exist');
        }

        $response = $this->asFile($file, 'export.zip', [
            'mimeType' => 'application/zip',
            'inline' => false,
        ]);

        $response->on(Response::EVENT_AFTER_SEND, function ($event) {
            @unlink($event->data);
            foreach ($this->unlinkFiles as $file) {
                @unlink($file);
            }
        }, $file);

        return $response;
    }

    #[NoReturn]
    public function actionExport(): void
    {
        App::maxPowerCaptain();

        $sse = new SSE();
        $serializer = $this->getSerializer();

        $token = $this->request->get('token');
        $post = \Craft::$app->cache->get("freeform-export-{$token}");
        if (!$post) {
            $sse->message('err', json_encode(['Invalid token']));
            $sse->message('exit', 'error');

            exit;
        }

        $password = $post['password'] ?? null;

        $exporter = \Craft::$container->get(FreeformFormsExporter::class);
        $exporter->setOptions($post);

        $formsByUid = [];
        foreach ($this->getFormsService()->getAllForms() as $form) {
            $formsByUid[$form->getUid()] = $form;
        }

        $sse->message('info', 'Collecting data');

        $collection = $exporter->collect();

        $this->announceTotals($sse, $collection);

        $chunks = array_filter([
            'forms.jsonl' => $collection->getForms(),
            'notifications.jsonl' => $collection->getTemplates()->getNotification(),
            'pdf-templates.jsonl' => $collection->getTemplates()->getPdf(),
            'formatting-templates.jsonl' => $collection->getTemplates()->getFormatting(),
            'success-templates.jsonl' => $collection->getTemplates()->getSuccess(),
            'integrations.jsonl' => $collection->getIntegrations(),
            'settings.json' => $collection->getSettings(),
        ]);

        $zipPath = tempnam(sys_get_temp_dir(), 'freeform-export-');

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath, \ZipArchive::CREATE)) {
            $sse->message('err', json_encode(['Could not create zip file']));
            $sse->message('exit', 'error');

            exit;
        }

        // Set password if one provided
        if ($password) {
            $zip->setPassword($password);
        }

        foreach ($chunks as $name => $chunk) {
            $file = pathinfo($name, \PATHINFO_FILENAME);

            $format = pathinfo($name, \PATHINFO_EXTENSION);
            $data = $serializer->serialize($chunk, $format);
            if (empty($data)) {
                continue;
            }

            $zip->addFromString($name, $data);
            if ($password) {
                $zip->setEncryptionName($name, \ZipArchive::EM_AES_256);
            }

            $sse->message('info', 'Exporting '.$file);
            $sse->message('reset', 1);
            $sse->message('progress', 1);
        }

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups(['export'])
            ->toArray()
        ;

        foreach ($collection->getFormSubmissions() as $formSubmissions) {
            $total = $formSubmissions->submissionBatchProcessor->total();
            if (!$total) {
                continue;
            }

            $uid = $formSubmissions->formUid;
            $form = $formsByUid[$uid] ?? null;
            $formName = $form ? $form->name : $uid;

            $sse->message('info', 'Exporting submissions for '.$formName);

            $tmp = tempnam(sys_get_temp_dir(), 'submissions-'.$uid);
            $this->unlinkFiles[] = $tmp;
            $file = fopen($tmp, 'w');

            $sse->message('reset', $total);

            foreach ($formSubmissions->submissionBatchProcessor->batch(100) as $submissions) {
                foreach ($submissions as $submission) {
                    $processed = \call_user_func($formSubmissions->getProcessor(), $submission);
                    $data = $serializer->encode($processed->toArray(), 'json', $context);
                    fwrite($file, $data.\PHP_EOL);
                }

                $sse->message('progress', \count($submissions));
            }

            fclose($file);

            $zip->addFile($tmp, 'submissions-'.$uid.'.jsonl');
            if ($password) {
                $zip->setEncryptionName('submissions-'.$uid.'.jsonl', \ZipArchive::EM_AES_256);
            }
        }

        $formattingTemplates = $collection->getTemplates()->getFormatting();
        $successTemplates = $collection->getTemplates()->getSuccess();

        if ($formattingTemplates->count() || $successTemplates->count()) {
            $zip->addEmptyDir('templates');
        }

        if ($formattingTemplates->count() > 0) {
            $zip->addEmptyDir('templates/formatting');

            foreach ($formattingTemplates as $template) {
                $filePath = $template->path;
                if (!preg_match('/\/index\.(twig|html)$/', $template->fileName)) {
                    $zip->addFile(
                        $template->path,
                        'templates/formatting/'.$template->fileName
                    );
                } else {
                    $folderPath = \dirname($filePath);
                    $templateFolder = basename($folderPath);
                    $this->addFolderToZip($zip, $folderPath, 'templates/formatting/'.$templateFolder);
                }
            }
        }

        if ($successTemplates->count() > 0) {
            $zip->addEmptyDir('templates/success');

            foreach ($successTemplates as $template) {
                $filePath = $template->path;
                if (!preg_match('/\/index\.(twig|html)$/', $template->fileName)) {
                    $zip->addFile(
                        $template->path,
                        'templates/success/'.$template->fileName
                    );
                } else {
                    $folderPath = \dirname($filePath);
                    $templateFolder = basename($folderPath);
                    $this->addFolderToZip($zip, $folderPath, 'templates/success/'.$templateFolder);
                }
            }
        }

        $zip->close();

        $token = CryptoHelper::getUniqueToken(14);
        \Craft::$app->cache->set(
            "freeform-export-file-{$token}",
            $zipPath,
            30
        );

        $sse->message('file-token', $token);
        $sse->message('exit', 'done');

        exit;
    }

    protected function get(): array|object
    {
        $exporter = \Craft::$container->get(ExpressFormsExporter::class);

        return $exporter->collect();
    }

    private function announceTotals(SSE $sse, FreeformDataset $dataset): void
    {
        $templates = $dataset->getTemplates();
        $forms = $dataset->getForms();
        $submissions = $dataset->getFormSubmissions();

        $sse->message(
            'total',
            array_sum([
                $templates->count(),
                $forms->count(),
                $submissions->getTotals(),
            ])
        );
    }

    private function addFolderToZip($zip, string $folderPath, string $prefix = ''): bool
    {
        $folderPath = rtrim($folderPath, '/\\').\DIRECTORY_SEPARATOR;
        $prefix = rtrim($prefix, '/\\').'/';

        // Open the directory
        $dir = opendir($folderPath);
        if (!$dir) {
            return false;
        }

        // Loop through the directory
        while (($file = readdir($dir)) !== false) {
            // Skip "." and ".."
            if ('.' == $file || '..' == $file) {
                continue;
            }

            // Full path to the file/directory
            $fullPath = $folderPath.$file;

            // Path in the ZIP file
            $localPath = $prefix.$file;

            // If it's a directory, add it recursively
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localPath);
                $this->addFolderToZip($zip, $fullPath, $localPath);
            } else {
                // If it's a file, add it to the ZIP archive
                $zip->addFile($fullPath, $localPath);
            }
        }

        closedir($dir);

        return true;
    }
}
