<?php

namespace Battleships\Http;

/**
 * HTTP Request Class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class Request
{
    /**
     * @var array
     */
    protected $params;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $data;

    public function __construct()
    {
        // set request method
        $this->method = $_SERVER['REQUEST_METHOD'];

        // set request parameters
        $urlInfo = pathinfo($_SERVER['PHP_SELF']);
        // remove public folder from URL but only once (in case it's "/")
        $urlParams = preg_replace("#^" . $urlInfo['dirname'] ."(/?)#", "", $_SERVER['REQUEST_URI'], 1);
        $this->params = explode("/", $urlParams);

        // set RAW POST DATA
        $this->data = file_get_contents("php://input");
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
