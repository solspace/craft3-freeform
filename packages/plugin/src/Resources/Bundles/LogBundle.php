<?php

namespace Solspace\Freeform\Resources\Bundles;

class LogBundle extends AbstractFreeformAssetBundle
{
    public function getStylesheets(): array
    {
        return [
            'js/external/highlightjs@11.11.1/github.min.css',
            'css/cp/logs/logs.css',
        ];
    }

    public function getScripts(): array
    {
        return [
            'js/external/highlightjs@11.11.1/highlight.min.js',
            'js/external/highlightjs@11.11.1/json.min.js',
            'js/scripts/cp/logs/index.js',
        ];
    }
}
