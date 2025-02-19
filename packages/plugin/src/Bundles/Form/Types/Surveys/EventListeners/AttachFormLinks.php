<?php

namespace Solspace\Freeform\Bundles\Form\Types\Surveys\EventListeners;

use craft\helpers\UrlHelper;
use Solspace\Freeform\Bundles\Form\Types\Surveys\Survey;
use Solspace\Freeform\Bundles\Transformers\Builder\Form\FormTransformer;
use Solspace\Freeform\Events\Forms\GenerateLinksEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class AttachFormLinks extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            FormTransformer::class,
            FormTransformer::EVENT_ATTACH_LINKS,
            function (GenerateLinksEvent $event) {
                $form = $event->getForm();
                if (!$form instanceof Survey) {
                    return;
                }

                $label = Freeform::t('Survey Results');

                $event->add(
                    $label,
                    'survey',
                    UrlHelper::cpUrl('freeform/surveys/'.$form->getHandle()),
                    'linkList',
                    true,
                );
            }
        );
    }

    public static function getPriority(): int
    {
        return 1500;
    }

    public static function isProOnly(): bool
    {
        return true;
    }
}
