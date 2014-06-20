<?php

namespace Battleships\Http;

class Request
{
    protected $params;
    protected $method;
    protected $data;

    public function __construct()
    {
        // set request method
        $this->method = $_SERVER['REQUEST_METHOD'];

        // set request paramters
        $urlInfo = pathinfo($_SERVER['PHP_SELF']);
        // remove public folder from URL but only once (in case it's "/")
        $urlParams = preg_replace("#^" . $urlInfo['dirname'] ."(/?)#", "", $_SERVER['REQUEST_URI'], 1);
        $this->params = explode("/", $urlParams);

        // set RAW POST DATA
        $this->data = file_get_contents("php://input");
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getData()
    {
        return $this->data;
    }
}
