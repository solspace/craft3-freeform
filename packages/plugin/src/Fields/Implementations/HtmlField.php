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

namespace Solspace\Freeform\Fields\Implementations;

use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Fields\Interfaces\NoEmailPresenceInterface;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\IsolatedTwig;

#[Type(
    name: 'HTML',
    typeShorthand: 'html',
    iconPath: __DIR__.'/Icons/html.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/html.ejs',
)]
class HtmlField extends AbstractField implements InputOnlyInterface, NoStorageInterface, NoEmailPresenceInterface
{
    protected string $instructions = '';
    protected bool $required = false;

    #[Input\Boolean(
        label: 'Allow Twig',
        instructions: 'Used to enable Twig in HTML blocks',
    )]
    protected bool $twig = false;

    #[Input\CodeEditor(
        label: 'HTML',
        instructions: 'The HTML content to be rendered',
    )]
    protected ?string $content = '';

    public function isTwig(): bool
    {
        return $this->twig;
    }

    public function getContent(): string
    {
        return $this->content ?? '';
    }

    public function getType(): string
    {
        return self::TYPE_HTML;
    }

    public function getInputHtml(): string
    {
        $content = $this->getContent();

        if ($this->isTwig()) {
            if (\Craft::$app->request->getIsCpRequest()) {
                return $content;
            }

            $settings = Freeform::getInstance()->settings->getSettingsModel()->defaults;
            if ($settings->twigInHtml) {
                $variables = [
                    'form' => $this->getForm(),
                    'fields' => $this->getForm()->getLayout()->getFields()->getStorableFields(),
                    'allFields' => $this->getForm()->getLayout()->getFields(),
                ];

                if ($settings->twigIsolation) {
                    return (new IsolatedTwig())->render($content, $variables);
                }

                return \Craft::$app->view->renderString($content, $variables);
            }
        }

        return $content;
    }

    public function includeInGqlSchema(): bool
    {
        return false;
    }
}
