<?php

namespace Solspace\Freeform\Library\Integrations\Types\Captchas;

use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Integrations\EnabledByDefault\EnabledByDefaultInterface;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;

interface CaptchaIntegrationInterface extends IntegrationInterface, EnabledByDefaultInterface
{
    public function validate(Form $form): void;

    public function getHtmlTag(Form $form): string;

    public function getScriptPaths(): array;

    public function getCaptchaHandle(): string;
}
