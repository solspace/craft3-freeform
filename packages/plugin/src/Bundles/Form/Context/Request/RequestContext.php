<?php

namespace Solspace\Freeform\Bundles\Form\Context\Request;

use Solspace\Freeform\Bundles\Translations\TranslationProvider;

class RequestContext
{
    public function __construct(
        private TranslationProvider $translationProvider,
    ) {
        new DefaultValuesContext($this->translationProvider);
        new OverrideContext();
        new EditSubmissionContext();
        new GetContext();
        new StorageContext();
        new PostContext();
        new GraphQLContext();
    }
}
