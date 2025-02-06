<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Transformers;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Fields\FieldInterface;

class FormMonitorFieldTransformer
{
    public function __construct(
        private PropertyProvider $propertyProvider,
    ) {}

    public function transform(FieldInterface $field): object
    {
        $typeClass = $field::class;
        $editableProperties = $this->propertyProvider->getEditableProperties($typeClass);

        $properties = ['type' => $typeClass];
        foreach ($editableProperties as $property) {
            $reflectionProperty = new \ReflectionProperty($field, $property->handle);

            $isAccessible = $reflectionProperty->isPublic();
            if (!$isAccessible) {
                $reflectionProperty->setAccessible(true);
            }

            $value = $reflectionProperty->getValue($field);

            if (!$isAccessible) {
                $reflectionProperty->setAccessible(false);
            }

            if ($property->transformer) {
                $value = $property->transformer->reverseTransform($value);
            }

            $properties[$property->handle] = $value;
        }

        unset($properties['fieldType']);

        return (object) $properties;
    }
}
