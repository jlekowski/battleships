<?php

namespace Battleships\Controller;

use Battleships\RouterInterface;
use Battleships\Exception\InvalidControllerException;

class ControllerFactory
{
    /**
     * Get controller
     * @param \Battleships\RouterInterface $oRouter
     * @param \Battleships\Http\Response $oResponse
     * @return \Battleships\Controller\Controller
     */
    public static function build(RouterInterface $oRouter, \Battleships\Http\Response $oResponse)
    {
        $controllerClass = __NAMESPACE__ . "\\" . ucfirst($oRouter->getControllerName()) . "Controller";

        if (!class_exists($controllerClass)) {
            throw new InvalidControllerException(
                sprintf("Could not find controller: %s", $oRouter->getControllerName())
            );
        }

        return new $controllerClass($oRouter, $oResponse);
    }
}
