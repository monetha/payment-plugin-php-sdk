<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;


use Monetha\Payload\AbstractPayload;
use Monetha\Response\AbstractResponse;
use Monetha\Response\Exception\ApiException;

abstract class AbstractRequest
{
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
     * @var string
     */
    private $apiUrlPrefix;

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

        if (!$this->uri) {
            $this->uri = $uri;
        }
    }

    /**
     * @return AbstractResponse
     * @throws ApiException
     */
    final public function send()
    {
        $response = $this->getResponse($this->payload);

        return $response;
    }

    private function getResponse(AbstractPayload $payload) {
        // TODO: timeout

        $options = [
            CURLOPT_URL => $this->apiUrlPrefix . $this->uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER =>  [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
        ];

        $options[CURLOPT_CUSTOMREQUEST] = $this->method;

        $body = (string) $payload;
        if ($this->method !== 'GET' && $body) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        $chSign = curl_init();
        curl_setopt_array($chSign, $options);

        $res = curl_exec($chSign);
        $error = curl_error($chSign);

        $responseCode = curl_getinfo($chSign, CURLINFO_HTTP_CODE);

        curl_close($chSign);

        if ($error) {
            $apiException = new ApiException();
            $apiException->setApiStatusCode($responseCode);
            $apiException->setApiErrorCode($responseCode);
            $apiException->setApiErrorMessage(sprintf(
                'Error: %s, Raw response: %s',
                $error,
                $res
            ));

            throw $apiException;
        }

        $resJson = json_decode($res);

        if (json_last_error() || !($resJson instanceof \stdClass)) {
            $jsonErrorMessage = json_last_error_msg();

            $apiException = new ApiException();
            $apiException->setApiStatusCode($responseCode);
            $apiException->setApiErrorCode('INVALID_JSON');
            $apiException->setApiErrorMessage(sprintf(
                'Error: %s, Raw response: %s',
                $jsonErrorMessage,
                $res
            ));

            throw $apiException;
        }

        if ($responseCode >= 300) {
            $apiException = new ApiException();
            $apiException->setApiStatusCode($responseCode);
            $apiException->setApiErrorCode(!empty($resJson->code) ? $resJson->code : $responseCode);
            $apiException->setApiErrorMessage(!empty($resJson->message) ? $resJson->message : $res);

            throw $apiException;
        }

        $this->response->setResponseJson($resJson);

        return $this->response;
    }
}