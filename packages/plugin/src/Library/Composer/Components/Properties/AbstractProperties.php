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

namespace Solspace\Freeform\Library\Composer\Components\Properties;

use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

abstract class AbstractProperties
{
    public const TYPE_STRING = 'string';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_ARRAY = 'array';
    public const TYPE_OBJECT = 'object';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';

    /** @var string */
    protected $type;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * AbstractProperties constructor.
     *
     * @throws ComposerException
     */
    public function __construct(array $properties, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->validateAndSetProperties($properties);
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Return a list of all property fields and their type.
     *
     * [propertyKey => propertyType, ..]
     * E.g. ["name" => "string", ..]
     */
    abstract protected function getPropertyManifest(): array;

    /**
     * @throws ComposerException
     */
    private function validateAndSetProperties(array $properties)
    {
        $manifest = $this->getPropertyManifest();

        // Forcing type to be mandatory
        $manifest['type'] = 'string';

        foreach ($properties as $key => $value) {
            if (!isset($manifest[$key])) {
                continue;
            }

            $expectedType = strtolower($manifest[$key]);

            switch ($expectedType) {
                case self::TYPE_BOOLEAN:
                    if (!\is_bool($value)) {
                        $value = \in_array(strtolower($value), ['1', 1, 'true'], true) ? true : false;
                    }

                    break;

                case self::TYPE_INTEGER:
                    $value = (int) $value;

                    break;

                case self::TYPE_DOUBLE:
                    $value = (float) $value;

                    break;

                case self::TYPE_FLOAT:
                    $value = (float) $value;

                    break;

                case self::TYPE_STRING:
                    $value = (string) $value;

                    break;

                case self::TYPE_OBJECT:
                    if ([] === $value) {
                        $value = (object) $value;
                    }

                    break;

                case self::TYPE_ARRAY:
                    if ($value == new \stdClass()) {
                        $value = (array) $value;
                    }

                    break;
            }

            $valueType = \gettype($value);
            if (self::TYPE_OBJECT === $valueType && self::TYPE_ARRAY === $expectedType) {
                $expectedType = self::TYPE_OBJECT;
            }

            if (!empty($value) && $valueType !== $expectedType) {
                throw new ComposerException(
                    $this->getTranslator()->translate(
                        "Value for '{key}' should be '{valueType}' but is '{expectedType}'",
                        [
                            'key' => $key,
                            'expectedType' => $expectedType,
                            'valueType' => $valueType,
                        ]
                    )
                );
            }

            $this->{$key} = $value;
        }
    }
}
