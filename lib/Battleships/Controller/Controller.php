<?php

namespace Battleships\Controller;

use Battleships\RouterInterface;
use Battleships\Http\Response;
//use Battleships\Game\Manager;
use Battleships\Misc;
use Battleships\Exception\InvalidActionException;

abstract class Controller
{
    protected $oResponse;
    protected $actionName;
    protected $params;
    protected $rawData;
    protected $data;
    protected $result;

    abstract protected function init();

    final public function __construct(RouterInterface $oRouter, Response $oResponse)
    {
        $this->oResponse = $oResponse;
        $this->actionName = $oRouter->getActionName();
        $this->params = $oRouter->getParams();
        $this->rawData = $oRouter->getData();
        $this->data = Misc::jsonDecode($this->rawData);
        $this->init();
    }

    final public function run()
    {
        $this->runAction();
        $this->dispatch();
    }

    public function __call($name, $arguments)
    {
        throw new InvalidActionException($name);
    }

    protected function getParam($paramName)
    {
        return isset($this->params[$paramName]) ? $this->params[$paramName] : null;
    }

    private function runAction()
    {
        $this->{$this->actionName . "Action"}();
    }

    private function dispatch()
    {
        $this->oResponse->setResult($this->result);
        $this->oResponse->dispatch();
    }
}
