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

namespace Solspace\Freeform\Models;

use craft\base\Model;
use Solspace\Freeform\Records\StatusRecord;

/**
 * Class Freeform_FieldModel.
 *
 * @property int    $id
 * @property string $name
 * @property string $handle
 * @property bool   $isDefault
 * @property string $color
 * @property int    $sortOrder
 */
class StatusModel extends Model implements \JsonSerializable
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $handle;

    /** @var bool */
    public $isDefault;

    /** @var string */
    public $color;

    /** @var int */
    public $sortOrder;

    /** @var string[] */
    private static $hexMap = [
        'green' => '#27AE60',
        'orange' => '#F2842D',
        'red' => '#D0021B',
        'yellow' => '#F1C40E',
        'pink' => '#FF50F2',
        'purple' => '#9B59B6',
        'blue' => '#0D99F2',
        'turquoise' => '#2CE0BD',
        'light' => '#CCD1D6',
        'grey' => '#98A3AE',
        'black' => '#32475E',
    ];

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    public static function create(): self
    {
        $colors = StatusRecord::getAllowedColors();
        shuffle($colors);
        $randomColor = reset($colors);

        $field = new static();
        $field->color = $randomColor;

        return $field;
    }

    public function getColorHex(): string
    {
        return self::$hexMap[$this->color] ?? '#FFFFFF';
    }

    /**
     * {@inheritDoc}
     */
    public function safeAttributes(): array
    {
        return [
            'name',
            'handle',
            'isDefault',
            'color',
            'sortOrder',
        ];
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize()
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'isDefault' => (bool) $this->isDefault,
            'color' => $this->color,
        ];
    }
}
