<?php

namespace Solspace\Freeform\Resources\Bundles\FormattingTemplates;

use craft\web\AssetBundle;

class MultipageAllFieldsBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@freeform-formatting-templates/multipage-all-fields';

        $this->js = ['_main.js'];
        $this->css = ['_main.css'];

        parent::init();
    }
}
