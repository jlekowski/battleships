<?php

/**
 * SOAP Server
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.3
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . "..") . DIRECTORY_SEPARATOR  . "config" . DIRECTORY_SEPARATOR . "config.php";;
require_once INCLUDE_PATH . "functions.php";

ini_set("soap.wsdl_cache_enabled", "0");

try {
    $oDB = new DB(DB_TYPE);
    $oServer = new SoapServer(WSDL_FILE);
    $oServer->setClass("BattleshipsSoap", new BattleshipsGame(), $oDB);
    $oServer->handle();
}
catch (SoapFault $e) {
    var_dump($e);
}
