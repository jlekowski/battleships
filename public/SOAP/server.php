<?php

/**
 * SOAP Server
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.4
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\DB;
use Battleships\Game\Data;
use Battleships\Game\Manager;
use Battleships\Misc;

ini_set("soap.wsdl_cache_enabled", "0");

try {
    $oManager = new Manager(new Data(), new DB(DB_TYPE));
    $oServer = new \SoapServer(WSDL_FILE);
    $oServer->setClass("Battleships\Soap\Server", $oManager);
    $oServer->handle();
} catch (\SoapFault $e) {
    Misc::log($e->getMessage());
}
