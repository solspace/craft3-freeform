<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class FlexboxBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/flexbox';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
