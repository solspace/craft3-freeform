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

namespace Solspace\Freeform\Library\Composer;

use Psr\Log\LoggerInterface;
use Solspace\Freeform\Form\Types\Regular;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Context;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Composer\Components\Properties;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\FormTypes\FormTypeInterface;
use Solspace\Freeform\Library\Translations\TranslatorInterface;
use Solspace\Freeform\Models\FormModel;

class Composer
{
    public const KEY_COMPOSER = 'composer';
    public const KEY_PROPERTIES = 'properties';
    public const KEY_LAYOUT = 'layout';
    public const KEY_CONTEXT = 'context';
    public const KEY_PAYMENT = 'payment';

    /** @var Form */
    private $form;

    /** @var FormModel */
    private $formModel;

    /** @var Context */
    private $context;

    /** @var Properties */
    private $properties;

    /** @var array */
    private $composerState;

    /** @var TranslatorInterface */
    private $translator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @throws ComposerException
     */
    public function __construct(
        FormModel $formModel,
        array $composerState = null,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->formModel = $formModel;
        $this->translator = $translator;
        $this->logger = $logger;

        $this->composerState = $composerState;
        $this->validateComposerData();
    }

    /**
     * @return Form
     */
    public function getForm(): Components\Form
    {
        return $this->form;
    }

    public function getComposerStateJSON(): string
    {
        $jsonObject = new \stdClass();
        $jsonObject->composer = new \stdClass();
        $jsonObject->composer->layout = $this->form->getLayout();
        $jsonObject->composer->properties = $this->properties;
        $jsonObject->context = $this->context;

        return json_encode($jsonObject);
    }

    /**
     * Removes the field from layout as well as from properties.
     *
     * @param int $id
     */
    public function removeFieldById($id)
    {
        $field = $this->form->getLayout()->getFieldById($id);

        $this->form->getLayout()->removeFieldFromData($field);
        $this->properties->removeHash($field->getHash());
    }

    /**
     * Removes property.
     *
     * @param string $hash
     */
    public function removeProperty($hash)
    {
        $this->properties->removeHash($hash);
    }

    /**
     * Validates all components and hydrates respective objects.
     *
     * @throws ComposerException
     */
    private function validateComposerData()
    {
        $composerState = $this->composerState;

        if (null === $composerState) {
            $this->setDefaults();

            return;
        }

        if (!isset($composerState[self::KEY_COMPOSER])) {
            throw new ComposerException(
                $this->translator->translate('No form builder data present')
            );
        }

        $composer = $composerState[self::KEY_COMPOSER];

        if (!isset($composer[self::KEY_PROPERTIES])) {
            throw new ComposerException(
                $this->translator->translate('Form Builder has no properties')
            );
        }

        $properties = $composer[self::KEY_PROPERTIES];
        $this->properties = new Properties($properties, $this->translator);

        if (!isset($composer[self::KEY_LAYOUT])) {
            $composer[self::KEY_LAYOUT] = [[]];
        }

        if (!isset($composerState[self::KEY_CONTEXT])) {
            throw new ComposerException(
                $this->translator->translate('No context specified')
            );
        }

        $this->context = new Context($composerState[self::KEY_CONTEXT]);

        if (!isset($properties[Properties::FORM_HASH])) {
            throw new ComposerException($this->translator->translate('No form settings specified'));
        }

        // XXX: maybe not very clean approach, but existing forms will fail to display payments tab otherwise
        if (!isset($properties[Properties::PAYMENT_HASH])) {
            $defaults = $this->getDefaultProperties();
            $this->properties->set(Properties::PAYMENT_HASH, $defaults[Properties::PAYMENT_HASH]);
        }

        /** @var FormTypeInterface $type */
        $type = $this->formModel->type;

        $this->form = new $type(
            $this->formModel,
            $this->properties,
            $composer[self::KEY_LAYOUT],
            $this->translator,
            $this->logger
        );
    }

    /**
     * This method sets defaults for all composer items
     * It happens if a new Form Model is created.
     */
    private function setDefaults()
    {
        $this->properties = new Properties($this->getDefaultProperties(), $this->translator);

        /** @var FormTypeInterface $type */
        $type = $this->formModel->type;

        $this->context = new Context([]);
        $this->form = new $type(
            $this->formModel,
            $this->properties,
            [[]],
            $this->translator,
            $this->logger
        );
    }

    private function getDefaultProperties(): array
    {
        $freeform = Freeform::getInstance();

        return [
            Properties::PAGE_PREFIX.'0' => [
                'type' => Properties::PAGE_PREFIX,
                'label' => 'Page 1',
            ],
            Properties::FORM_HASH => [
                'type' => Properties::FORM_HASH,
                'name' => '',
                'formType' => Regular::class,
                'handle' => '',
                'color' => '#'.substr(md5(random_int(111, 999).time()), 0, 6),
                'submissionTitleFormat' => '{{ dateCreated|date("Y-m-d H:i:s") }}',
                'description' => '',
                'formTemplate' => $freeform->forms->getDefaultFormattingTemplate(),
                'returnUrl' => '',
                'storeData' => true,
                'defaultStatus' => $freeform->statuses->getDefaultStatusId(),
                'ajaxEnabled' => $freeform->forms->isAjaxEnabledByDefault(),
            ],
            Properties::VALIDATION_HASH => [
                'type' => Properties::VALIDATION_HASH,
                'validationType' => 'submit',
                'successMessage' => '',
                'errorMessage' => '',
            ],
            Properties::INTEGRATION_HASH => [
                'type' => Properties::INTEGRATION_HASH,
                'integrationId' => 0,
                'mapping' => new \stdClass(),
            ],
            Properties::CONNECTIONS_HASH => [
                'type' => Properties::CONNECTIONS_HASH,
                'list' => null,
            ],
            Properties::RULES_HASH => [
                'type' => Properties::RULES_HASH,
                'list' => new \stdClass(),
            ],
            Properties::ADMIN_NOTIFICATIONS_HASH => [
                'type' => Properties::ADMIN_NOTIFICATIONS_HASH,
                'notificationId' => 0,
                'recipients' => '',
            ],
            Properties::PAYMENT_HASH => [
                'type' => Properties::PAYMENT_HASH,
                'integrationId' => 0,
                'mapping' => new \stdClass(),
            ],
        ];
    }
}
