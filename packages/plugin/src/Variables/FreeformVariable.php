<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Variables;

use craft\helpers\Template;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\PaymentGateways\Common\PaymentFieldInterface;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Services\StripePaymentService;
use Solspace\Freeform\Library\Helpers\EditionHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Models\Payments\PaymentModel;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\Pro\Payments\PaymentRecord;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\LoggerService;
use Solspace\Freeform\Services\NotificationsService;
use Solspace\Freeform\Services\SettingsService;
use Twig\Markup;
use yii\base\Event;
use yii\db\Exception;
use yii\web\NotFoundHttpException;

class FreeformVariable
{
    public array $siteTemplatesDirectories = [];

    /**
     * @param int|string $handleOrId
     */
    public function form($handleOrId, ?array $properties = null): ?Form
    {
        $site = SitesHelper::getFrontendSiteHandle();
        $form = $this->getFormService()->getFormByHandleOrId($handleOrId, $site);
        if (!$form) {
            return null;
        }

        return $form->setProperties($properties);
    }

    /**
     * @return Form[]
     */
    public function forms(): array
    {
        $formService = $this->getFormService();

        $sites = SitesHelper::getSiteHandlesForFrontend();
        $forms = $formService->getAllForms(sites: $sites);

        return $forms ?: [];
    }

    public function plugin(): Freeform
    {
        return Freeform::getInstance();
    }

    public function submissionCount(Form $form): int
    {
        return Freeform::getInstance()->submissions->getSubmissionCount([$form->getId()]);
    }

    public function submissions(?array $attributes = null): SubmissionQuery
    {
        $query = Submission::find();

        if (isset($attributes['includeSpam'])) {
            $isSpam = $attributes['includeSpam'] ? null : false;
            unset($attributes['includeSpam']);
            $query->isSpam($isSpam);
        } else {
            $query->isSpam(false);
        }

        if ($attributes) {
            \Craft::configure($query, $attributes);
        }

        return $query;
    }

    /**
     * @throws Exception
     */
    public function deleteSubmissionByToken(string $token): bool
    {
        if (empty($token) || Submission::OPT_IN_DATA_TOKEN_LENGTH !== \strlen($token)) {
            return false;
        }

        $query = Submission::find()->limit(1)->token($token);

        return Freeform::getInstance()->submissions->delete($query, true);
    }

    public function getSettings(): Settings
    {
        return $this->getSettingsService()->getSettingsModel();
    }

    public function name(): string
    {
        return Freeform::getInstance()->name;
    }

    public function getSettingsNavigation(): array
    {
        return $this->getSettingsService()->getSettingsNavigation();
    }

    /**
     * @deprecated use the ::loadFreeformPlugin() method from now on
     */
    public function loadFreeformScripts(): Markup
    {
        return $this->loadFreeformPlugin();
    }

    public function loadScripts(array $scripts = []): Markup
    {
        $event = new CollectScriptsEvent($scripts);
        Event::trigger(Form::class, Form::EVENT_COLLECT_SCRIPTS, $event);

        return new Markup($event->getHtml(), 'utf-8');
    }

    public function loadFormSpecificScripts(Form|string $form): Markup
    {
        if (!$form instanceof Form) {
            $form = $this->getFormService()->getFormByHandleOrId($form);
        }

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $event = new RenderTagEvent($form, collectScripts: true);

        Event::trigger(Form::class, Form::EVENT_RENDER_BEFORE_OPEN_TAG, $event);
        Event::trigger(Form::class, Form::EVENT_RENDER_AFTER_OPEN_TAG, $event);
        Event::trigger(Form::class, Form::EVENT_RENDER_BEFORE_CLOSING_TAG, $event);
        Event::trigger(Form::class, Form::EVENT_RENDER_AFTER_CLOSING_TAG, $event);

        $scripts = $event->getScripts();
        $styles = $event->getStyles();

        $output = '';

        foreach ($scripts as $script) {
            $output .= '<script type="text/javascript" src="'.$script.'"></script>'.\PHP_EOL;
        }

        foreach ($styles as $style) {
            $output .= '<link rel="stylesheet" href="'.$style.'" />'.\PHP_EOL;
        }

        return Template::raw($output);
    }

    public function loadFreeformPlugin(?string $attributes = null, ?string $styleAttributes = null): Markup
    {
        $jsPath = $this->getSettingsService()->getPluginJsPath();
        $cssPath = $this->getSettingsService()->getPluginCssPath();

        $js = \Craft::$app->assetManager->getPublishedUrl('@freeform-resources', true, $jsPath);
        $css = \Craft::$app->assetManager->getPublishedUrl('@freeform-resources', true, $cssPath);

        $output = '<script type="text/javascript" src="'.$js.'" '.$attributes.'></script>'.\PHP_EOL;
        $output .= '<link rel="stylesheet" href="'.$css.'" '.$styleAttributes.' />';

        return Template::raw($output);
    }

    public function getLoggerService(): LoggerService
    {
        return Freeform::getInstance()->logger;
    }

    public function isPro(): bool
    {
        return Freeform::getInstance()->isPro();
    }

    public function getEdition(): EditionHelper
    {
        return Freeform::getInstance()->edition();
    }

    public function getVersion(?int $marks = null): string
    {
        $version = Freeform::getInstance()->version;

        if (null === $marks) {
            return $version;
        }

        $points = explode('.', $version);
        $points = \array_slice($points, 0, $marks);

        return implode('.', $points);
    }

    public function notifications(): NotificationsService
    {
        return Freeform::getInstance()->notifications;
    }

    public function getAllSiteTemplatesDirectories(): array
    {
        $this->siteTemplatesDirectories = [];

        $siteTemplatesPath = \Craft::$app->getPath()->getSiteTemplatesPath();

        $this->getSiteTemplatesDirectories($siteTemplatesPath, $siteTemplatesPath);

        return $this->siteTemplatesDirectories;
    }

    public function payments(int|Submission|SubmissionQuery $submission, ?string $paymentFieldHandle = null): null|array|PaymentModel
    {
        if (is_numeric($submission)) {
            $submission = Freeform::getInstance()->submissions->getSubmissionById($submission);
        }

        if ($submission instanceof SubmissionQuery) {
            $submission = $submission->one();
        }

        if (!$submission) {
            return null;
        }

        $form = $submission->getForm();

        if ($paymentFieldHandle) {
            $field = $form->getLayout()->getFields()->get($paymentFieldHandle);
            $fieldIds = [$field->getId()];
        } else {
            $fieldIds = array_map(
                fn (PaymentFieldInterface $field) => $field->getId(),
                $form
                    ->getLayout()
                    ->getFields(PaymentFieldInterface::class)
                    ->getIterator()
                    ->getArrayCopy()
            );
        }

        $records = PaymentRecord::find()
            ->where(['submissionId' => $submission->id])
            ->andWhere(['fieldId' => $fieldIds])
            ->all()
        ;

        $paymentService = \Craft::$container->get(StripePaymentService::class);
        $payments = array_map(
            fn (PaymentRecord $record) => $paymentService->recordToModel($record),
            $records
        );

        if (!$payments) {
            return null;
        }

        if (1 === \count($payments)) {
            return $payments[0];
        }

        return $payments;
    }

    private function getSiteTemplatesDirectories(string $siteTemplatesPath, string $currentPath): void
    {
        foreach (new \DirectoryIterator($currentPath) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                if ($fileInfo->isDir()) {
                    $this->siteTemplatesDirectories[] = str_replace($siteTemplatesPath.'/', '', $fileInfo->getPathname());

                    $this->getSiteTemplatesDirectories($siteTemplatesPath, $fileInfo->getPathname());
                }
            }
        }
    }

    private function getFormService(): FormsService
    {
        return Freeform::getInstance()->forms;
    }

    private function getSettingsService(): SettingsService
    {
        return Freeform::getInstance()->settings;
    }
}
