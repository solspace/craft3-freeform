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

namespace Solspace\Freeform\Library\Factories;

use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Composer\Components\Properties\FieldProperties;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Stringy\Stringy;

class ComposerFieldFactory
{
    private static $defaultFieldNamespace = 'Solspace\Freeform\Fields';
    private static $proFieldNamespace = 'Solspace\Freeform\Fields\Pro';
    private static $paymentsFieldNamespace = 'Solspace\Freeform\Fields\Pro\Payments';

    public static function createFromProperties(
        Form $form,
        FieldProperties $properties,
        $pageIndex
    ): AbstractField {
        /** @var AbstractField $className */
        $className = $properties->getType();
        if (FieldInterface::TYPE_DYNAMIC_RECIPIENTS === $className) {
            $className = 'dynamic_recipient';
        }

        if (FieldInterface::TYPE_FILE === $className) {
            $className = 'file_upload';
        }

        if (FieldInterface::TYPE_CREDIT_CARD_DETAILS === $className) {
            $className = 'credit_card_details';
        }

        if (FieldInterface::TYPE_OPINION_SCALE === $className) {
            $className = 'opinion_scale';
        }

        $className = (string) Stringy::create($className)->upperCamelize();
        $className .= 'Field';

        if (class_exists(self::$defaultFieldNamespace.'\\'.$className)) {
            $className = self::$defaultFieldNamespace.'\\'.$className;
        } elseif (class_exists(self::$proFieldNamespace.'\\'.$className)) {
            $className = self::$proFieldNamespace.'\\'.$className;
        } elseif (class_exists(self::$paymentsFieldNamespace.'\\'.$className)) {
            $className = self::$paymentsFieldNamespace.'\\'.$className;
        } else {
            throw new ComposerException(
                $form->getTranslator()->translate(
                    'Could not create a field of type {type}',
                    ['type' => $properties->getType()]
                )
            );
        }

        return $className::createFromProperties($form, $properties, $pageIndex);
    }
}
