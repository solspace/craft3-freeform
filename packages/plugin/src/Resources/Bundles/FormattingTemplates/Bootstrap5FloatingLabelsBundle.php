<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class Bootstrap5FloatingLabelsBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@Solspace/Freeform/templates/_templates/formatting/bootstrap-5-floating-labels';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
