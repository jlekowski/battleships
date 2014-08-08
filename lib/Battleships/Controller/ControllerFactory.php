<?php

namespace Battleships\Controller;

use Battleships\Http\Response;
use Battleships\RouterInterface;
use Battleships\Exception\InvalidControllerException;

/**
 * API Controller Factory
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class ControllerFactory
{
    /**
     * Get instance of a controller
     * @param \Battleships\RouterInterface $oRouter
     * @param \Battleships\Http\Response $oResponse
     * @return \Battleships\Controller\Controller
     * @throws \Battleships\Exception\InvalidControllerException
     */
    public static function build(RouterInterface $oRouter, Response $oResponse)
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