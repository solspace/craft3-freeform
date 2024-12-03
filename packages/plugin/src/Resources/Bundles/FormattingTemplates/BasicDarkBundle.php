<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class BasicDarkBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/basic-dark';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
