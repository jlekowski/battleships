<?php

namespace Battleships\Rest;

use Battleships\RouterInterface;
use Battleships\Http\Request;

class Router implements RouterInterface
{
    /**
     * HTTP Request method mapped to method prefix
     * @var array
     */
    private $requestToAction = array(
        'GET' => "get",
        'POST' => "add",
        'PUT' => "update",
        'DELETE' => "delete"
    );
    /**
     * Map of parameters from REST HTTP Request URL
     * @var array
     */
    private $routingMap = array("controller", "controllerParam", "action", "actionParam");
    /**
     * URL Parameters passed in REST request
     * @var array
     */
    private $params = [];
    /**
     * Data passed in the HTTP Request (e.g. RAW POST in JSON format)
     * @var string
     */
    private $data;
    /**
     * HTTP Request action name
     * @var string
     */
    private $actionName;

    public function __construct(Request $oRequest)
    {
        // set params
        $requestParams = $oRequest->getParams();
        foreach ($this->routingMap as $key => $paramName) {
            $this->params[$paramName] = isset($requestParams[$key]) ? $requestParams[$key] : null;
        }

        // set data
        $this->data = $oRequest->getData();

        // set action name
        $requestMethod = $oRequest->getMethod();
        $actionPrefix = isset($this->requestToAction[$requestMethod])
            ? $this->requestToAction[$requestMethod]
            : '';
        $this->actionName = $actionPrefix . ucfirst($this->params['action']);
    }

    public function getControllerName()
    {
        return $this->params['controller'];
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getData()
    {
        return $this->data;
    }
}
