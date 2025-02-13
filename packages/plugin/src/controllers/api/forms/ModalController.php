<?php

namespace Solspace\Freeform\controllers\api\forms;

use craft\helpers\StringHelper;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Transformers\Builder\Form\FormTransformer;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Form\Types\Regular;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\FormModal\CreateFormModal;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Event;

class ModalController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private PropertyProvider $propertyProvider,
        private FormTransformer $formTransformer,
        private SettingsService $settingsService,
    ) {
        parent::__construct($id, $module, $config);
    }

    protected function get(): array|object
    {
        return $this->propertyProvider->getEditableProperties(CreateFormModal::class);
    }

    protected function post(null|int|string $id = null): null|array|object
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_CREATE);

        $data = json_decode($this->request->getRawBody(), false);
        $data->uid = StringHelper::UUID();
        if (!isset($data->type)) {
            $data->type = Regular::class;
        }

        if (!isset($data->formattingTemplate)) {
            $data->formattingTemplate = '';
        }

        $regex = '/[^a-zA-Z_0-9]+/i';
        if ($this->settingsService->getSettingsModel()->allowDashesInFieldHandles) {
            $regex = '/[^a-zA-Z\-_0-9]+/i';
        }

        $handle = preg_replace($regex, ' ', $data->name);
        $handle = StringHelper::toHandle($handle);

        $data->settings = (object) ['general' => (object) []];
        $data->settings->general->name = $data->name;
        $data->settings->general->type = $data->type;
        $data->settings->general->handle = $handle;
        $data->settings->general->formattingTemplate = $data->formattingTemplate;
        $data->settings->general->storeData = $data->storeData;

        if (!empty($data->sites)) {
            $data->settings->general->sites = $data->sites;
        } else {
            $data->settings->general->sites = SitesHelper::getEditableSiteIds();
        }

        $persistData = (object) ['form' => $data];

        $event = new PersistFormEvent($persistData);

        if (empty(trim($data->name))) {
            $event->addErrorsToResponse('form', ['name' => ['Name cannot be empty']]);
        }

        Event::trigger(FormsController::class, FormsController::EVENT_CREATE_FORM, $event);
        Event::trigger(FormsController::class, FormsController::EVENT_UPSERT_FORM, $event);

        $this->response->statusCode = $event->getStatus() ?? 201;
        if ($event->hasErrors()) {
            return $event->getResponseData();
        }

        return $this->formTransformer->transform($event->getForm());
    }
}
