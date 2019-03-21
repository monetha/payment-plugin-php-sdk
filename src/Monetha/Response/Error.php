<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;


class Error extends AbstractResponse
{
    /**
     * @var int
     */
    private $statusCode = 0;

    /**
     * @var bool
     */
    protected $isError = true;

    /**
     * @param string|int $statusCode
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
    }
}