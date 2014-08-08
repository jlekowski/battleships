<?php

namespace Battleships\Rest;

use Battleships\Misc;
use Battleships\Exception\CurlException;
use Battleships\Exception\ClientException;

/**
 * Battleships\Rest\AbstractClient abstract class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
abstract class AbstractClient
{
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var resource
     */
    private $ch;

    /**
     * Initiate CURL connection
     * @param string $baseUrl
     */
    final public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * Close CURL connection
     */
    final public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     *
     * @param string $request
     * @param string $method
     * @param \stdClass $data
     * @return mixed REST API response
     * @throws \Battleships\Exception\CurlException
     * @throws \Battleships\Exception\ClientException
     */
    final protected function call($request, $method, \stdClass $data = null)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($this->ch, CURLOPT_URL, $this->baseUrl . $request);

        $curlResponse = curl_exec($this->ch);
        if ($curlResponse === false) {
            throw new CurlException(curl_error($this->ch), curl_errno($this->ch));
        }

        $response = Misc::jsonDecode($curlResponse);
        if ($response->error !== null) {
            throw new ClientException($response->error->message, $response->error->code);
        }

        return $response->result;
    }
}
