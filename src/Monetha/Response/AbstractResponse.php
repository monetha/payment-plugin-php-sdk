<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;

abstract class AbstractResponse
{
    /**
     * @var array
     */
    protected $responseArray = [];

    /**
     * @var bool
     */
    protected $isError = false;

    /**
     * @return bool
     */
    public function isError() {
        return $this->isError;
    }

    /**
     * @param array $responseArray
     */
    public function setResponseArray(array $responseArray)
    {
        $this->responseArray = $responseArray;
    }

    /**
     * @return array
     */
    public function getResponseArray()
    {
        return $this->responseArray;
    }
}