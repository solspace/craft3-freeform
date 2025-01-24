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

class PlanDetails
{
    /** @var string */
    private $name;

    /** @var float */
    private $amount;

    /** @var string */
    private $currency;

    /** @var string */
    private $interval;

    /** @var string */
    private $formName;

    /** @var string */
    private $formHash;

    /**
     * PaymentDetails constructor.
     *
     * @param string $name
     */
    public function __construct(
        string $name = null,
        float $amount,
        string $currency,
        string $interval,
        string $formName = '',
        string $formHash = ''
    ) {
        $this->name = $name;
        $this->amount = $amount;
        $this->currency = strtolower($currency);
        $this->interval = strtolower($interval);
        $this->formName = $formName;
        $this->formHash = $formHash;
    }

    public function getName(): string
    {
        return $this->name ?? $this->generateName();
    }

    public function getId(): string
    {
        return $this->generateId();
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getFormHash(): string
    {
        return $this->formHash;
    }

    protected function generateName(): string
    {
        $currency = strtoupper($this->currency);
        $name = "{$this->amount} {$currency} {$this->interval}";
        if ($this->formName) {
            $name = "'{$this->formName}' form ".$name;
        }

        return $name;
    }

    protected function generateId(): string
    {
        $name = @iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $this->name);
        $name = strtolower($name);
        $name = str_replace(' ', '_', $name);
        $name = preg_replace('/\W+/', '', $name);
        $amount = preg_replace('/[^0-9]/', '_', (string) $this->amount);

        $id = "{$amount}_{$this->currency}_{$this->interval}";

        if ($this->formHash) {
            $id = "{$this->formHash}_{$id}";
        }
        if ($name) {
            $id = "{$name}_{$id}";
        }

        return "freeform_{$id}";
    }
}
