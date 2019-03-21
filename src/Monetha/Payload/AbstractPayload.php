<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:52
 */

namespace Monetha\Payload;


abstract class AbstractPayload
{
    /**
     * @var array
     */
    private $payload;

    /**
     * AbstractPayload constructor.
     * @param array $payload
     */
    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return \GuzzleHttp\json_encode($this->payload);
    }
}