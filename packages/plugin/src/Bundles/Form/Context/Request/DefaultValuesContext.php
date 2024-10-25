<?php

namespace Solspace\Freeform\Bundles\Form\Context\Request;

use Faker\Factory;
use Faker\Generator;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Events\FormEventInterface;
use Solspace\Freeform\Fields\Implementations\CheckboxField;
use Solspace\Freeform\Fields\Interfaces\DefaultValueInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Helpers\IsolatedTwig;
use Solspace\Freeform\Library\Helpers\TwigHelper;
use yii\base\Event;

class DefaultValuesContext
{
    public function __construct(
        private TranslationProvider $translationProvider,
    ) {
        Event::on(Form::class, Form::EVENT_REGISTER_CONTEXT, [$this, 'handleDefaultValues']);
        Event::on(Form::class, Form::EVENT_BEFORE_HANDLE_REQUEST, [$this, 'handleDefaultValues']);
        Event::on(Form::class, Form::EVENT_QUICK_LOAD, [$this, 'handleDefaultValues']);
    }

    public function handleDefaultValues(FormEventInterface $event): void
    {
        $form = $event->getForm();
        if ($form->isGraphQLPosted()) {
            return;
        }

        $fields = $form->getLayout()->getFields(DefaultValueInterface::class);
        foreach ($fields as $field) {
            if ($field instanceof CheckboxField) {
                continue;
            }

            $value = $this
                ->translationProvider
                ->getTranslation(
                    $field,
                    $field->getUid(),
                    'defaultValue',
                    $field->getDefaultValue(),
                )
            ;

            if (TwigHelper::isTwigValue($value) && preg_match('/\bfaker\.\b/', $value)) {
                $value = $this->getTwig()->render($value, ['faker' => $this->getFaker()]);
            }

            $field->setValue($value);
        }
    }

    private function getTwig(): IsolatedTwig
    {
        static $instance;
        if (null === $instance) {
            $instance = new IsolatedTwig();
        }

        return $instance;
    }

    private function getFaker(): Generator
    {
        static $instance;
        if (null === $instance) {
            $instance = Factory::create(\Craft::$app->getLocale()->id);
        }

        return $instance;
    }
}
