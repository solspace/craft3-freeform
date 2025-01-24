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

namespace Solspace\Freeform\Library\Translations;

class CraftTranslator implements TranslatorInterface
{
    /**
     * Translates a string
     * Replaces any variables in the $string with variables from $variables
     * User brackets to specify variables in string.
     *
     * Example:
     * Translation string: "Hello, {firstName}!"
     * Variables: ["firstName": "Icarus"]
     * End result: "Hello, Icarus!"
     *
     * @param string $string
     */
    public function translate($string, array $variables = [], string $category = 'freeform'): string
    {
        return \Craft::t($category, $string, $variables);
    }
}
