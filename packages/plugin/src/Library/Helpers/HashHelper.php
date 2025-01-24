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

namespace Solspace\Freeform\Library\Helpers;

use Hashids\Hashids;

class HashHelper
{
    public const SALT = 'composer';
    public const MIN_LENGTH = 9;

    /** @var Hashids */
    private static $hashids = [];

    public static function hash(int $id = null, string $salt = null): string
    {
        return self::getHashids($salt)->encode($id);
    }

    /**
     * @return null|int
     */
    public static function decode(string $hash, string $salt = null)
    {
        $idList = self::getHashids($salt)->decode($hash);
        if (!$idList) {
            return null;
        }

        return array_pop($idList);
    }

    public static function decodeMultiple(string $hash, string $salt = null): array
    {
        return self::getHashids($salt)->decode($hash);
    }

    /**
     * @param mixed $value
     * @param int   $length
     */
    public static function sha1($value, int $length = null, int $offset = 0): string
    {
        $hash = sha1($value);

        if ($length) {
            return substr($hash, $offset, $length);
        }

        return $hash;
    }

    private static function getHashids(string $salt = null): Hashids
    {
        $key = sha1($salt);
        if (!isset(self::$hashids[$key])) {
            $salt .= \Craft::$app->getConfig()->getGeneral()->securityKey;

            self::$hashids[$key] = new Hashids($salt, self::MIN_LENGTH);
        }

        return self::$hashids[$key];
    }
}
