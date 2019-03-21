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
    private $apiUrl;

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
    private $uri;

    /**
     * @var string
     */
    private $token;

    /**
     * @var Client
     */
    private $client;

    /**
     * AbstractRequest constructor.
     * @param AbstractPayload $payload
     * @param $token
     * @param $apiUrlPrefix
     * @param null $uri
     */
    public function __construct(AbstractPayload $payload, $token, $apiUrlPrefix, $uri = null)
    {
        $this->payload = $payload;
        $this->token = $token;
        $this->apiUrlPrefix = $apiUrlPrefix;

        $this->client = new Client(
            [
                'base_uri' => sprintf('%s://%s:%s', $scheme, $host, $port),
                'timeout'  => 30,
                'headers'  => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        if (is_null($uri)) {
            return;
        }

        $this->uri = $uri;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function send()
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
     * @param string $method
     * @return Request
     */
    private function buildRequest($uri, $method = 'POST') {
        return new Request($method, $uri);
    }
}