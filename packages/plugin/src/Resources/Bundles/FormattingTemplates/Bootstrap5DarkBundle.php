<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class Bootstrap5DarkBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@Solspace/Freeform/templates/_templates/formatting/bootstrap-5-dark';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
