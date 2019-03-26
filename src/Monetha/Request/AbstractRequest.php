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
use Monetha\Response\Error;

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
     * @return AbstractResponse|Error
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
            $errorResponse = new Error();
            $errorResponse->setStatusCode($responseCode);
            $errorResponse->setResponseArray([
                'code' => $responseCode,
                'message' => sprintf(
                    'Error: %s, Raw response: %s',
                    $error,
                    $res
                ),
            ]);

            return $errorResponse;
        }

        $resJson = json_decode($res);


        if (json_last_error()) {
            $jsonErrorMessage = json_last_error_msg();
            $jsonError = new Error();
            $jsonError->setStatusCode($responseCode);
            $jsonError->setResponseArray([
                'code' => 'INVALID_JSON',
                'message' => sprintf(
                    'Error: %s, Raw response: %s',
                    $jsonErrorMessage,
                    $res
                ),
            ]);

            return $jsonError;
        }

        if ($responseCode >= 300 && $resJson instanceof \stdClass) {
            $errorResponse = new Error();
            $errorResponse->setStatusCode($responseCode);
            $errorResponse->setResponseArray([
                'code' => !empty($resJson->code) ? $resJson->code : $responseCode,
                'message' => !empty($resJson->message) ? $resJson->message : $res,
            ]);

            return $errorResponse;
        }

        $this->response->setResponseArray((array) $resJson);

        return $this->response;
    }
}