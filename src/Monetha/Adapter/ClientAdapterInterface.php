<?php

namespace Monetha\Adapter;

interface ClientAdapterInterface {
    public function getContactName();

    public function getContactEmail();

    public function getContactPhoneNumber();

    public function getCountryIsoCode();

    public function getAddress();

    public function getCity();

    public function getZipCode();
}
