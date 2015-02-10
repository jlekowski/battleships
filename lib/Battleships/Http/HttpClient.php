<?php

namespace Battleships\Http;

use Battleships\Exception\HttpClientException;

/**
 * Battleships\Http\HttpClient class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6.1
 *
 */
class HttpClient
{
    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * @var resource
     */
    protected $ch;

    /**
     * Initiate CURL connection
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->ch = curl_init();
//        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
//        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * Close CURL connection
     */
    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * @param string $request
     * @param string $method
     * @param \stdClass $data
     * @return mixed CURL response
     * @throws \Battleships\Exception\HttpClientException
     */
    public function call($request, $method, \stdClass $data = null)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($this->ch, CURLOPT_URL, $this->baseUrl . $request);

        $curlResponse = curl_exec($this->ch);
        if ($curlResponse === false) {
            throw new HttpClientException(curl_error($this->ch), curl_errno($this->ch));
        }

        return $curlResponse;
    }

    /**
     * @return mixed
     */
    public function getCallInfo()
    {
        return curl_getinfo($this->ch);
    }
}
