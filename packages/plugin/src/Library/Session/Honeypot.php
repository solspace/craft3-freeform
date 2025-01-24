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

namespace Solspace\Freeform\Library\Session;

use Solspace\Freeform\Freeform;

class Honeypot implements \JsonSerializable
{
    public const NAME_PREFIX = 'freeform_form_handle';

    /** @var string */
    private $name;

    /** @var string */
    private $hash;

    /** @var int */
    private $timestamp;

    /**
     * Honeypot constructor.
     */
    public function __construct(bool $isEnhanced = false)
    {
        $this->name = $this->getHoneypotInputName($isEnhanced);
        $this->hash = $isEnhanced ? $this->generateHash() : '';
        $this->timestamp = time();
    }

    public static function createFromUnserializedData(array $data): self
    {
        $honeypot = new self();
        $honeypot->name = $data['name'];
        $honeypot->hash = $data['hash'];
        $honeypot->timestamp = $data['timestamp'];

        return $honeypot;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'hash' => $this->getHash(),
            'timestamp' => $this->getTimestamp(),
        ];
    }

    private function generateUniqueName(string $prefix): string
    {
        $hash = $this->generateHash(6);

        return $prefix.'_'.$hash;
    }

    /**
     * @param int $length
     */
    private function generateHash($length = 9): string
    {
        $random = time().random_int(111, 999).(time() + 999);
        $hash = sha1($random);

        return substr($hash, 0, $length);
    }

    /**
     * @return mixed|string
     */
    private function getHoneypotInputName(bool $isEnhanced)
    {
        $inputName = Freeform::getInstance()->settings->getSettingsModel()->customHoneypotName;
        if (!$inputName) {
            $inputName = self::NAME_PREFIX;
        }

        return $isEnhanced ? $this->generateUniqueName($inputName) : $inputName;
    }
}
