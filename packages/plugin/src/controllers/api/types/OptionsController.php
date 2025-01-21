<?php

namespace Solspace\Freeform\controllers\api\types;

use Solspace\Freeform\Bundles\Form\Limiting\LimitedUsers\LimitedUserChecker;
use Solspace\Freeform\Bundles\Transformers\Options\OptionTypeTransformer;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\OptionTypesProvider;
use yii\web\Response;

class OptionsController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private OptionTypeTransformer $optionTypeTransformer,
        private OptionTypesProvider $optionTypesProvider,
        private LimitedUserChecker $checker,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionGetElementTypes(): Response
    {
        $types = $this->optionTypesProvider->getElementTypes();
        $allowedTypes = [];
        foreach ($types as $type) {
            if ($this->checker->can('layout.options.elements.types', $type::class)) {
                $allowedTypes[] = $type;
            }
        }

        return $this->getSerializedTypes($allowedTypes);
    }

    public function actionGetPredefinedTypes(): Response
    {
        $types = $this->optionTypesProvider->getPredefinedTypes();
        $allowedTypes = [];
        foreach ($types as $type) {
            if ($this->checker->can('layout.options.predefined.types', $type::class)) {
                $allowedTypes[] = $type;
            }
        }

        return $this->getSerializedTypes($allowedTypes);
    }

    public function actionOptions(string $type): Response
    {
        $this->requirePostRequest();

        $request = \Craft::$app->getRequest();
        $formId = $request->post('formId');
        $fieldId = $request->post('fieldId');
        $query = $request->post('query');

        $options = $this->getOptionsService()->getOptions($formId, $fieldId, $query);

        return $this->asJson($options);
    }

    private function getSerializedTypes(array $types): Response
    {
        $serialized = [];
        foreach ($types as $type) {
            $serialized[] = $this->optionTypeTransformer->transform($type);
        }

        return $this->asSerializedJson($serialized);
    }
}
