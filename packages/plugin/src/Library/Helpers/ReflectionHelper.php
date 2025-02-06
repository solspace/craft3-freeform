<?php

namespace Solspace\Freeform\Library\Helpers;

class ReflectionHelper
{
    public static function isInstanceOf(object|string $object, string $className): bool
    {
        if (!class_exists($object) && !interface_exists($object)) {
            return false;
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return false;
        }

        if (\is_string($object) && $object === $className) {
            return true;
        }

        $reflectedClass = new \ReflectionClass($object);
        $isSubclassOf = $reflectedClass->isSubclassOf($className);

        $implementsInterface = false;
        if (interface_exists($className)) {
            try {
                $implementsInterface = $reflectedClass->implementsInterface($className);
            } catch (\ReflectionException) {
                $implementsInterface = false;
            }
        }

        return $isSubclassOf || $implementsInterface;
    }
}
