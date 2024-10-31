<?php

namespace Solspace\Freeform\Bundles\Form\Context;

use Solspace\Freeform\Bundles\Form\Context\Pages\PageContext;
use Solspace\Freeform\Bundles\Form\Context\Request\RequestContext;
use Solspace\Freeform\Bundles\Form\Context\Session\SessionContext;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Library\Bundles\FeatureBundle;

class ContextBundle extends FeatureBundle
{
    public function __construct(
        private TranslationProvider $translationProvider,
    ) {
        new SessionContext();
        new PageContext();
        new RequestContext($this->translationProvider);
    }
}
