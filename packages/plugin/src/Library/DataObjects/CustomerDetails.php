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

class CustomerDetails
{
    /** @var string */
    private $name;

    /** @var float */
    private $email;

    /** @var string */
    private $phone;

    /** @var AddressDetails */
    private $address;

    /**
     * CustomerDetails constructor.
     *
     * @param string         $name
     * @param string         $email
     * @param string         $phone
     * @param AddressDetails $address
     */
    public function __construct($name, $email, $phone, $address)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
    }

    /**
     * Creates CustomerDetails instance with properties from provided array.
     */
    public static function fromArray(array $customer): self
    {
        $firstName = $customer['first_name'] ?? null;
        $lastName = $customer['last_name'] ?? null;
        $name = $customer['name'] ?? implode(' ', [$firstName, $lastName]);

        $email = $customer['email'] ?? null;
        $email = \is_array($email) ? $email[0] : $email;
        $phone = $customer['phone'] ?? null;
        $address = null;

        if (isset($customer['address'])) {
            if (is_a($address, AddressDetails::name)) {
                $address = $customer['address'];
            } elseif (\is_array($address)) {
                $addressProps = $customer['address'];
            }
        } else {
            $addressProps = $customer;
        }

        if ($addressProps) {
            $line1 = $addressProps['line1'] ?? null;
            $line2 = $addressProps['line2'] ?? null;
            $city = $addressProps['city'] ?? null;
            $postalCode = $addressProps['postal_code'] ?? null;
            $state = $addressProps['state'] ?? null;
            $country = $addressProps['country'] ?? null;

            // validating if we have any address data at all
            if ($line1 || $line2) {
                $address = new AddressDetails($line1, $line2, $city, $postalCode, $state, $country);
            }
        }

        return new self($name, $email, $phone, $address);
    }

    /**
     * Get the value of name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the value of phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Get the value of address.
     *
     * @return AddressDetails
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function toStripeConstructArray(): array
    {
        $data = [
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
        ];

        if ($this->getAddress()) {
            $data['address'] = [
                'line1' => $this->getAddress()->getLine1(),
                'line2' => $this->getAddress()->getLine2(),
                'city' => $this->getAddress()->getCity(),
                'country' => $this->getAddress()->getCountry(),
                'postal_code' => $this->getAddress()->getPostalCode(),
                'state' => $this->getAddress()->getState(),
            ];
        }

        return $data;
    }
}
