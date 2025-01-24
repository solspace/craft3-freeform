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

use Solspace\Freeform\Fields\Pro\RatingField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\ObscureValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\OptionsInterface;

trait SingleValueTrait
{
    /** @var string */
    protected $value;

    /**
     * @return null|string
     */
    public function getValue(): mixed
    {
        return (string) $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(mixed $value): FieldInterface
    {
        $this->value = $value;

        if ($this instanceof OptionsInterface) {
            $updatedOptions = [];

            if ($this instanceof ObscureValueInterface) {
                $objectValue = $this->getValue();
                if (is_numeric($value)) {
                    $objectValue = $this->getActualValue($this->getValue());
                }
            } elseif ($this instanceof RatingField) {
                // Prevents GraphQL from triggering an number constraint violation
                if (!empty($this->getValue())) {
                    $objectValue = $this->getValue();
                } else {
                    $this->value = $objectValue = null;
                }
            } else {
                $objectValue = $this->getValue();
            }

            foreach ($this->getOptions() as $option) {
                $updatedOptions[] = new Option(
                    $option->getLabel(),
                    $option->getValue(),
                    $option->getValue() === (string) $objectValue
                );
            }

            $this->options = $updatedOptions;
        }

        return $this;
    }
}
