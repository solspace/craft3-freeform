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

use Solspace\Freeform\Library\Composer\Components\Properties;

class PageProperties extends AbstractProperties
{
    /** @var string */
    protected $label;

    public static function getKey(int $index): string
    {
        return Properties::PAGE_PREFIX.$index;
    }

    public static function getIndex(string $key): int
    {
        return (int) str_replace(Properties::PAGE_PREFIX, '', $key);
    }

    public function getLabel(): string
    {
        return $this->label ?: 'Page';
    }

    /**
     * Return a list of all property fields and their type.
     *
     * [propertyKey => propertyType, ..]
     * E.g. ["name" => "string", ..]
     */
    protected function getPropertyManifest(): array
    {
        return ['label' => self::TYPE_STRING];
    }
}
