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

namespace Solspace\Freeform\Library\DataObjects;

use Solspace\Commons\Helpers\StringHelper;

class FormTemplate implements \JsonSerializable
{
    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $name;

    /**
     * FormTemplate constructor.
     *
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->fileName = pathinfo($filePath, \PATHINFO_BASENAME);
        $this->name = StringHelper::camelize(StringHelper::humanize(pathinfo($filePath, \PATHINFO_FILENAME)));
        $this->name = str_replace(['-', '_'], ' ', $this->name);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'fileName' => $this->getFileName(),
            'filePath' => $this->getFilePath(),
        ];
    }
}
