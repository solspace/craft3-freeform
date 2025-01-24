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

use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Library\Composer\Components\Form;
use Twig\Markup;

interface FormHandlerInterface
{
    /** @deprecated */
    public const EVENT_BEFORE_SUBMIT = 'beforeSubmit';

    /** @deprecated */
    public const EVENT_AFTER_SUBMIT = 'afterSubmit';
    public const EVENT_PAGE_JUMP = 'pageJump';
    public const EVENT_BEFORE_SAVE = 'beforeSave';
    public const EVENT_AFTER_SAVE = 'afterSave';
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /** @deprecated */
    public const EVENT_RENDER_OPENING_TAG = 'renderOpeningTag';

    /** @deprecated */
    public const EVENT_RENDER_CLOSING_TAG = 'renderClosingTag';

    /** @deprecated  */
    public const EVENT_FORM_VALIDATE = 'validateForm';

    /** @deprecated  */
    public const EVENT_AFTER_FORM_VALIDATE = 'afterValidateForm';

    /** @deprecated */
    public const EVENT_ATTACH_FORM_ATTRIBUTES = 'attachFormAttributes';

    /** @deprecated */
    public const EVENT_AFTER_GENERATE_RETURN_URL = 'afterGenerateReturnUrl';

    /**
     * @param string $templateName
     *
     * @return null|Markup
     */
    public function renderFormTemplate(Form $form, $templateName);

    public function renderSuccessTemplate(Form $form);

    /**
     * Increments the spam block counter by 1.
     *
     * @return int - new spam block count
     */
    public function incrementSpamBlockCount(Form $form): int;

    public function isSpamBehaviourSimulateSuccess(): bool;

    public function isSpamBehaviourReloadForm(): bool;

    public function isSpamFolderEnabled(): bool;

    public function isAjaxEnabledByDefault(): bool;

    public function shouldScrollToAnchor(Form $form): bool;

    public function isAutoscrollToErrorsEnabled(): bool;

    public function isFormSubmitDisable(): bool;

    public function getDefaultFormattingTemplate(): string;

    /**
     * Do something before the form is saved
     * Return bool determines whether the form should be saved or not.
     *
     * @deprecated use Form::EVENT_SUBMIT instead
     */
    public function onBeforeSubmit(Form $form): bool;

    /**
     * Do something after the form is saved.
     *
     * @deprecated use Form::EVENT_AFTER_SUBMIT instead
     */
    public function onAfterSubmit(Form $form, Submission $submission = null);

    /**
     * Allows 3rd party scripts to override the page that the form will jump to.
     *
     * @return null|int $pageIndex
     */
    public function onBeforePageJump(Form $form);

    /**
     * Attach anything to the form after opening tag.
     *
     * @deprecated Use Form::EVENT_RENDER_AFTER_OPEN_TAG event instead
     */
    public function onRenderOpeningTag(Form $form): string;

    /**
     * Attach anything to the form before the closing tag.
     *
     * @deprecated Use Form::EVENT_RENDER_AFTER_CLOSING_TAG event instead
     */
    public function onRenderClosingTag(Form $form): string;

    /**
     * Attach any custom form attributes to the form tag.
     *
     * @deprecated Use Form::EVENT_ATTACH_TAG_ATTRIBUTES event instead
     */
    public function onAttachFormAttributes(Form $form, array $attributes = []);

    /**
     * @deprecated Use Form::EVENT_BEFORE_VALIDATE. This event will no longer be used in Freeform 4.x.
     */
    public function onFormValidate(Form $form);

    /**
     * @deprecated Use Form::EVENT_AFTER_VALIDATE. This event will no longer be used in Freeform 4.x.
     */
    public function onAfterFormValidate(Form $form);

    /**
     * @deprecated Use Form::EVENT_GENERATE_RETURN_URL. This event will no longer be used in Freeform 4.x
     */
    public function onAfterGenerateReturnUrl(Form $form, Submission $submission = null, string $returnUrl = null);
}
