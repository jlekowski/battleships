<?php

/**
 * REST Server
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Http\Request;
use Battleships\Http\Response;
use Battleships\Rest\Router;
use Battleships\Controller\ControllerFactory;

// to use long calls
ini_set('max_execution_time', CHECK_UPDATES_TIMEOUT);

try {
    $oRequest = new Request();
    $oResponse = new Response($oRequest);
    $oRouter = new Router($oRequest);
    $controller = ControllerFactory::build($oRouter, $oResponse);
    $controller->run();
} catch (\Exception $e) {
    $oResponse->setError($e);
    $oResponse->dispatch();
}
