<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class GridBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@Solspace/Freeform/templates/_templates/formatting/grid';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
