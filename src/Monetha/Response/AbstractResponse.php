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
     * AbstractResponse constructor.
     *
     * @param array $dataResponseItem
     */
    public function __construct(array $dataResponseItem) {
        $this->responseArray = $dataResponseItem;
    }

    /**
     * @return bool
     */
    public function isError() {
        return $this->isError;
    }
}