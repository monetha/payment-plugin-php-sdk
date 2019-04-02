<?php

namespace Monetha\Adapter;

interface WebHookAdapterInterface {
    public function cancel($note);

    public function finalize();

    public function authorize();
}
