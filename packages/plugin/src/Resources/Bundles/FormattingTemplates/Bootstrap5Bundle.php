<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class Bootstrap5Bundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/bootstrap-5';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
