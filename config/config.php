<?php

/**
 * General config
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.3
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 */

error_reporting(E_ALL & ~E_STRICT); // set E_ALL for debugging

define('ROOT_PATH',      dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDE_PATH',   ROOT_PATH . "include" . DIRECTORY_SEPARATOR);
define('SOAP_PATH',      ROOT_PATH . "public" . DIRECTORY_SEPARATOR . "SOAP" . DIRECTORY_SEPARATOR);
define('WSDL_FILE',      SOAP_PATH . "battleships.wsdl");
define('WSDL_HOST',      "http://dev.lekowski.pl/battleships/public"); // change for your host
define('WSDL_URL',       WSDL_HOST . "/SOAP/battleships.wsdl");
define('DB_TYPE',        "SQLITE");
define('SQLITE_PATH',    ROOT_PATH . "db" . DIRECTORY_SEPARATOR);
define('SQLITE_FILE',    "battleships.sqlite"); // change here if your db directory is open to public
define('MYSQL_USER',     "");
define('MYSQL_PASS',     "");
define('MYSQL_DB',       "");
define('MYSQL_HOST',     "localhost");
define('LOG_PATH',       ROOT_PATH . "log" . DIRECTORY_SEPARATOR);
define('LOG_FILE',       "error.log"); // change here if your db directory is open to public

define('CHECK_UPDATES_TIMEOUT',  120);   // AJAX call timeout in seconds
define('CHECK_UPDATES_INTERVAL', 2);     // interval between updates checks in seconds
define('CHECK_UPDATES_COUNT',    3);    // how many times to check for updates

ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", LOG_PATH.LOG_FILE);

function __autoload($className)
{
    require_once INCLUDE_PATH . $className . "Class.php";
}
