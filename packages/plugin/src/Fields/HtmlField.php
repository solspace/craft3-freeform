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

namespace Solspace\Freeform\Fields;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\DefaultFieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\SingleValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\SingleStaticValueTrait;
use Solspace\Freeform\Library\Helpers\IsolatedTwig;
use Solspace\Freeform\Models\Settings;

class HtmlField extends AbstractField implements DefaultFieldInterface, SingleValueInterface, InputOnlyInterface, NoStorageInterface
{
    use SingleStaticValueTrait;

    protected $twig;

    public function isTwig(): bool
    {
        return (bool) $this->twig;
    }

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_HTML;
    }

    /**
     * Outputs the HTML of input.
     */
    public function getInputHtml(): string
    {
        if ($this->isTwig()) {
            if (\Craft::$app->request->getIsCpRequest()) {
                return $this->getValue();
            }

            /** @var Settings $settings */
            $settings = Freeform::getInstance()->getSettings();
            if ($settings->twigInHtml) {
                $variables = [
                    'form' => $this->getForm(),
                    'fields' => $this->getForm()->getLayout()->getStorableFields(),
                    'allFields' => $this->getForm()->getLayout()->getFields(),
                ];

                if ($settings->twigInHtmlIsolatedMode) {
                    return (new IsolatedTwig())->render($this->getValue(), $variables);
                }

                return \Craft::$app->view->renderString($this->getValue(), $variables);
            }
        }

        return $this->getValue();
    }

    public function includeInGqlSchema(): bool
    {
        return false;
    }
}
