<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Database;

use Solspace\Freeform\Form\Form;
use Twig\Markup;

interface FormHandlerInterface
{
    public const EVENT_BEFORE_SAVE = 'beforeSave';
    public const EVENT_AFTER_SAVE = 'afterSave';
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    public const EVENT_AFTER_DELETE = 'afterDelete';

    public function renderFormTemplate(Form $form, string $templateName): ?Markup;

    public function renderSuccessTemplate(Form $form);

    /**
     * Increments the spam block counter by 1.
     *
     * @return int - new spam block count
     */
    public function incrementSpamBlockCount(Form $form): int;

    public function isSpamBehaviorSimulateSuccess(): bool;

    public function isSpamBehaviorReloadForm(): bool;

    public function isSpamFolderEnabled(): bool;

    public function isAjaxEnabledByDefault(): bool;

    public function shouldScrollToAnchor(Form $form): bool;

    public function isAutoscrollToErrorsEnabled(): bool;

    public function isFormSubmitDisable(): bool;

    public function getDefaultFormattingTemplate(): string;
}
