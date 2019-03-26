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
use Psr\Http\Message\ResponseInterface;

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
     * @var AbstractResponse
     */
    protected $response;

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
                'timeout'  => 15,
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

    /**
     * @return AbstractResponse|Error
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    final public function send()
    {
        $responseToReturn = $this->response;
        try {
            $response = $this->getResponse($this->uri, $this->payload);
            $json = $response->getBody()->getContents();
            $responseArray = \GuzzleHttp\json_decode($json, true);

        } catch (RequestException $e) {
            $response = $e->getResponse();
            $json = $response->getBody()->getContents();
            $responseArray = \GuzzleHttp\json_decode($json, true);

            $responseToReturn = new Error();
            $responseToReturn->setStatusCode($e->getCode());
        } catch (InvalidArgumentException $e) {
            // invalid JSON
            $responseToLog = !is_null($json) ? $json : '';
            $responseToReturn = new Error();
            $responseArray = [
                'code' => 'INVALID_JSON',
                'message' => sprintf(
                    'Exception: %, Raw response: %s',
                    $e->getMessage(),
                    $responseToLog
                ),
            ];
        }

        $responseToReturn->setResponseArray($responseArray);

        return $responseToReturn;
    }

    /**
     * @param string $uri
     * @param AbstractPayload $payload
     * @return ResponseInterface
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

        return $response;
    }

    /**
     * @param string $uri
     * @return Request
     */
    private function buildRequest($uri) {
        return new Request($this->method, $uri);
    }
}