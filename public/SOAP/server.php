<?php

/**
 * SOAP Server
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\DB;
use Battleships\DBConfig;
use Battleships\Game\Data;
use Battleships\Game\Manager;
use Battleships\Misc;

// don't cached WSDL file
ini_set("soap.wsdl_cache_enabled", "0");
// to use long calls
ini_set('max_execution_time', CHECK_UPDATES_TIMEOUT);

try {
    $oDB = new DB(new DBConfig());
    $oManager = new Manager(new Data(), $oDB);
    $oServer = new \SoapServer(WSDL_FILE);
    $oServer->setClass("Battleships\Soap\Server", $oManager);
    $oServer->handle();
} catch (\Exception $e) {
    Misc::log($e);
    $oServer->fault($e->getCode(), $e->getMessage());
}
