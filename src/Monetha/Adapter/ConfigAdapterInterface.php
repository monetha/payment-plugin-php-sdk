<?php

namespace Monetha\Adapter;

interface ConfigAdapterInterface {
    public function getMerchantSecret();

    public function getMthApiKey();

    public function getIsTestMode();
}
