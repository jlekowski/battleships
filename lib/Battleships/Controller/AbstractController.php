<?php

namespace Battleships\Controller;

use Battleships\RouterInterface;
use Battleships\Http\Response;
use Battleships\Game\Manager;
use Battleships\Misc;
use Battleships\Exception\InvalidActionException;

/**
 * Abstract API Controller
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
abstract class AbstractController
{
    /**
     * @var \Battleships\Http\Response
     */
    protected $oResponse;
    /**
     * @var \Battleships\Game\Manager
     */
    protected $oManager;
    /**
     * @var string
     */
    protected $actionName;
    /**
     * @var array
     */
    protected $params;
    /**
     * @var string
     */
    protected $rawData;
    /**
     * @var mixed
     */
    protected $data;
    /**
     * @var
     */
    protected $result;

    /**
     * @return void
     */
    abstract protected function init();

    /**
     * @param \Battleships\RouterInterface $oRouter
     * @param \Battleships\Http\Response $oResponse
     * @param \Battleships\Game\Manager $oManager
     * @throws \Battleships\Exception\JsonException
     */
    final public function __construct(RouterInterface $oRouter, Response $oResponse, Manager $oManager)
    {
        $this->oResponse = $oResponse;
        $this->oManager = $oManager;
        $this->actionName = $oRouter->getActionName();
        $this->params = $oRouter->getParams();
        $this->rawData = $oRouter->getData();
        $this->data = Misc::jsonDecode($this->rawData);
        $this->init();
    }

    /**
     * Run controller
     */
    final public function run()
    {
        $this->runAction();
        $this->dispatch();
    }

    /**
     * Catch invalid action names
     * @param string $name
     * @param array $arguments
     * @throws \Battleships\Exception\InvalidActionException
     */
    public function __call($name, $arguments)
    {
        throw new InvalidActionException($name);
    }

    /**
     * @param string $paramName
     * @return mixed
     */
    protected function getParam($paramName)
    {
        return isset($this->params[$paramName]) ? $this->params[$paramName] : null;
    }

    /**
     * Run action method
     */
    private function runAction()
    {
        $this->{$this->actionName . "Action"}();
    }

    /**
     * Dispatch response
     */
    private function dispatch()
    {
        $this->oResponse->setResult($this->result);
        $this->oResponse->dispatch();
    }
}
