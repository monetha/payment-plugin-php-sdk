<?php

namespace Monetha\Adapter;

use stdClass;
use Address;

class ClientAdapter implements ClientAdapterInterface {

    /**
     * @var Address
     */
    private $address;

    /**
     * @var string
     */
    private $email;

    public function __construct(Address $address, stdClass $customerDetails)
    {
//        $phoneNumber = $address->phone_mobile ? $address->phone_mobile : $address->phone;
//        $iso_code = \Country::getIsoById($address->id_country);
//
////        $clientBody = array(
////            'contact_name' => $address->firstname.' '.$address->lastname,
////            'contact_email' => $customerDetails->email,
////            'contact_phone_number' => preg_replace('/\D/', '', $phoneNumber),
////            'country_code_iso' => $iso_code,
////            'address' => $address->address1,
////            'city' => $address->city,
////            'zipcode' => $address->postcode
////        );

        $this->address = $address;
        $this->email = $customerDetails->email;
    }

    /**
     * @return string
     */
    public function getContactName() {
        return $this->address->firstname . ' ' . $this->address->lastname;
    }

    /**
     * @return string
     */
    public function getContactEmail() {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getContactPhoneNumber() {
        $phoneNumber = $this->address->phone_mobile ? $this->address->phone_mobile : $this->address->phone;

        return preg_replace('/\D/', '', $phoneNumber);
    }

    /**
     * @return string
     */
    public function getCountryIsoCode() {
        $iso_code = \Country::getIsoById($this->address->id_country);

        return $iso_code;
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->address->address1;
    }

    /**
     * @return string
     */
    public function getCity() {
        return $this->address->city;
    }

    /**
     * @return string
     */
    public function getZipCode() {
        return $this->address->postcode;
    }
}
