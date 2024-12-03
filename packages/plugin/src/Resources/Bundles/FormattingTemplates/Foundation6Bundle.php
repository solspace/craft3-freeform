<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class Foundation6Bundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/foundation-6';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
