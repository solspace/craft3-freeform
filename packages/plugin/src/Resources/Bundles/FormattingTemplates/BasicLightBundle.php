<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class BasicLightBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/basic-light';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
