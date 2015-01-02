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
use Battleships\Game\Manager;
use Battleships\Game\Data;
use Battleships\DB;
use Battleships\DBConfig;
use Battleships\Controller\ControllerFactory;

// to use long calls
ini_set('max_execution_time', CHECK_UPDATES_TIMEOUT);

try {
    $oRequest = new Request();
    $oResponse = new Response($oRequest);
    $oRouter = new Router($oRequest);
    $oDB = new DB(new DBConfig());
    $oManager = new Manager(new Data(), $oDB);
    $controller = ControllerFactory::build($oRouter, $oResponse, $oManager);
    $controller->run();
} catch (\Exception $e) {
    $oResponse->setError($e);
    $oResponse->dispatch();
}
