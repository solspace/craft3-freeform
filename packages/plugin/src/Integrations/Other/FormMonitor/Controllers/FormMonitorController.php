<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Controllers;

use craft\db\Query;
use GuzzleHttp\Exception\BadResponseException;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\IntegrationRecord;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\LoggerService;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormMonitorController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private FormsService $formsService,
        private FormIntegrationsProvider $formIntegrationsProvider,
        private IntegrationClientProvider $clientProvider,
        private LoggerService $loggerService,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionAvailableForms(): Response
    {
        $formIds = (new Query())
            ->select('fi.[[formId]]')
            ->from(FormIntegrationRecord::TABLE.' fi')
            ->innerJoin(IntegrationRecord::TABLE.' i', 'fi.[[integrationId]] = i.[[id]]')
            ->where([
                'i.[[class]]' => FormMonitor::class,
                'fi.[[enabled]]' => true,
            ])
            ->column()
        ;

        return $this->asJson($formIds);
    }

    public function actionTests(?int $id = null): Response
    {
        $form = $this->formsService->getFormById($id);
        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $formMonitor = $this->formIntegrationsProvider->getFirstForForm($form, FormMonitor::class);
        if (!$formMonitor) {
            throw new NotFoundHttpException('Form Monitor integration not found');
        }

        $client = $this->clientProvider->getAuthorizedClient($formMonitor);

        try {
            $tests = $formMonitor->fetchTests($client, $form, [
                'limit' => 100,
                'offset' => 0,
            ]);
        } catch (BadResponseException $exception) {
            $this
                ->loggerService
                ->getLogger('Form Monitor')
                ->error((string) $exception->getResponse()->getBody())
            ;

            // TODO: remove this test code

            return $this->asJson([
                [
                    'id' => 1,
                    'formId' => 3,
                    'status' => 'fail',
                    'response' => (string) $exception->getResponse()->getBody(),
                    'responseCode' => $exception->getCode(),
                    'dateAttempted' => '2021-01-01T00:00:00Z',
                    'dateCompleted' => '2024-12-22T00:00:00Z',
                ],
                [
                    'id' => 2,
                    'formId' => 3,
                    'status' => 'success',
                    'response' => null,
                    'responseCode' => 201,
                    'dateAttempted' => '2021-01-01T00:00:00Z',
                    'dateCompleted' => '2021-01-01T00:00:00Z',
                ],
                [
                    'id' => 3,
                    'formId' => 3,
                    'status' => 'success',
                    'response' => null,
                    'responseCode' => 200,
                    'dateAttempted' => '2021-01-01T00:00:00Z',
                    'dateCompleted' => '2021-01-01T00:00:00Z',
                ],
            ]);
        }

        return $this->asJson($tests);
    }

    public function actionStats(): Response
    {
        try {
            $integration = (new Query())
                ->select(['*'])
                ->from(IntegrationRecord::TABLE)
                ->where([
                    'class' => FormMonitor::class,
                    'enabled' => true,
                ])
                ->one()
            ;

            if (!$integration) {
                return $this->asJson([]);
            }

            $formMonitor = $this->formIntegrationsProvider->getById($integration['id']);
            if (!$formMonitor instanceof FormMonitor) {
                return $this->asJson([]);
            }

            $client = $this->clientProvider->getAuthorizedClient($formMonitor);
            $stats = $formMonitor->fetchStats($client);

            return $this->asJson($stats);
        } catch (\Exception $exception) {
            $this->loggerService
                ->getLogger('Form Monitor')
                ->error($exception->getMessage())
            ;

            return $this->asJson([]);
        }
    }
}
