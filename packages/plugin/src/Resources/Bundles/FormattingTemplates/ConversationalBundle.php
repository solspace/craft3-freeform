<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class ConversationalBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/conversational';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
