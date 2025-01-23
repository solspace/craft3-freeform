<?php

namespace Solspace\Freeform\Library\Helpers;

class ArrayHelper
{
    /**
     * Returns true if any one element in the array passes the callable check.
     */
    public static function some(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if ($fn($value)) {
                return true;
            }
        }

        return false;
    }

    public static function someRecursive(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if (\is_array($value)) {
                if (self::someRecursive($value, $fn)) {
                    return true;
                }
            } else {
                if ($fn($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if every element in the $array returns true on the callback call.
     */
    public static function every(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if (!$fn($value)) {
                return false;
            }
        }

        return true;
    }

    public static function everyRecursive(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if (\is_array($value)) {
                if (!self::everyRecursive($value, $fn)) {
                    return false;
                }
            } else {
                if (!$fn($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function keyFlatten(array $array, ?int $depth = null, string $separator = '.'): array
    {
        $recursiveIterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));

        $result = [];
        foreach ($recursiveIterator as $leafValue) {
            $keys = [];

            $maxDepth = min($depth ?? $recursiveIterator->getDepth(), $recursiveIterator->getDepth());
            foreach (range(0, $maxDepth) as $currentDepth) {
                $keys[] = $recursiveIterator->getSubIterator($currentDepth)->key();
            }
            $result[implode($separator, $keys)] = $leafValue;
        }

        return $result;
    }

    public static function generate(int $iterations, callable $fn): array
    {
        $result = [];
        for ($i = 0; $i < $iterations; ++$i) {
            [$key, $value] = $fn($i);

            $result[$key] = $value;
        }

        return $result;
    }
}
