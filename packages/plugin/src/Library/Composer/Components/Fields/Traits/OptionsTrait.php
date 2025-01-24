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

namespace Solspace\Freeform\Library\Composer\Components\Fields\Traits;

use Solspace\Freeform\Fields\DynamicRecipientField;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;

trait OptionsTrait
{
    use OptionsKeyValuePairTrait;

    /** @var Option[] */
    protected $options;

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        $value = $this->getValue();
        if (!\is_array($value)) {
            $value = [$value];
        }

        $areIndexes = false;
        if ($this instanceof DynamicRecipientField && $value) {
            $areIndexes = true;
            foreach ($value as $val) {
                if (!is_numeric($val)) {
                    $areIndexes = false;
                }
            }
        }

        $options = [];
        foreach ($this->options as $index => $option) {
            if ($areIndexes) {
                $isChecked = \in_array($index, $value, false);
            } else {
                $isChecked = \in_array($option->getValue(), $value, true);
            }

            $options[] = new Option($option->getLabel(), $option->getValue(), $isChecked);
        }

        return $options;
    }
}
