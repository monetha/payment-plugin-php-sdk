<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Monetha\Payload\AbstractPayload;
use InvalidArgumentException;
use Monetha\Response\AbstractResponse;
use Monetha\Response\Error;

abstract class AbstractRequest
{
    /**
     * @var string
     */
    private $apiUrlPrefix;

    /**
     * @var AbstractPayload
     */
    private $payload;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    private $token;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * AbstractRequest constructor.
     * @param AbstractPayload $payload
     * @param string $token
     * @param string $apiUrlPrefix
     * @param string|null $uri
     */
    public function __construct(AbstractPayload $payload, $token, $apiUrlPrefix, $uri = null)
    {
        $this->payload = $payload;
        $this->token = $token;
        $this->apiUrlPrefix = $apiUrlPrefix;

        $this->client = new Client(
            [
                'base_uri' => $apiUrlPrefix,
                'timeout'  => 30,
                'headers'  => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        if (!$this->uri) {
            $this->uri = $uri;
        }
    }

//    /**
//     * @return AbstractResponse
//     * @throws \GuzzleHttp\Exception\GuzzleException
//     */
//    abstract public function send();

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makeRequest()
    {
        $responseArray = $this->getResponse($this->uri, $this->payload);

        return $responseArray;
    }

    /**
     * @param string $uri
     * @param AbstractPayload $payload
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getResponse($uri, AbstractPayload $payload) {
        $request = $this->buildRequest($uri);

        $response = $this->client->send(
            $request,
            [
                'body' => (string) $payload,
            ]
        );

        $json = $response->getBody()->getContents();
        $arrayResponse = \GuzzleHttp\json_decode($json, true);

        return $arrayResponse;
    }

//    private function handleException(RequestException $e) {
//        if ($e instanceof InvalidArgumentException) {
//
//            $errorResponse = new Error([
//                'message' => $e->getMessage(),
//            ]);
//            $errorResponse->setStatusCode($e->getCode());
//
//            return $errorResponse;
//        }
//
//        $response = $e->getResponse();
//
//        $content = $response->getBody()->getContents();
//        $responseArray = \GuzzleHttp\json_decode($content, true);
//        $errorResponse = new Error($responseArray);
//
//        $statusCode = $response->getStatusCode();
//        $errorResponse->setStatusCode($statusCode);
//
//        return $errorResponse;
//    }

    /**
     * @param string $uri
     * @return Request
     */
    private function buildRequest($uri) {
        return new Request($this->method, $uri);
    }
}