<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class Tailwind3Bundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/tailwind-3';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
