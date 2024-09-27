<?php

namespace Solspace\Freeform\Bundles\Form\HiddenInputs;

use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Fields\Implementations\Pro\CalculationField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class CalculationFormFields extends FeatureBundle
{
    public function __construct()
    {
        Event::on(Form::class, Form::EVENT_RENDER_AFTER_OPEN_TAG, [$this, 'attachInput']);
    }

    public function attachInput(RenderTagEvent $event)
    {
        $calculationFields = $event->getForm()->getFields()->getList(CalculationField::class);

        foreach ($calculationFields as $field) {
            if (!$field->canRender()) {
                $event->addChunk($field->renderInput());
            }
        }
    }
}
