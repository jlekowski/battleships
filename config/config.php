<?php

/**
 * General config
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 */

error_reporting(E_ALL); // set E_ALL for debugging

session_start();

define('ROOT_PATH',      dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDE_PATH',   ROOT_PATH . "include" . DIRECTORY_SEPARATOR);
define('SQLITE_PATH',    ROOT_PATH . "db" . DIRECTORY_SEPARATOR);
define('SQLITE_FILE',    "battleships.sqlite"); // change here if your db directory is open to public

define('CHECK_UPDATES_TIMEOUT',  120);   // AJAX call timeout in seconds
define('CHECK_UPDATES_INTERVAL', 2);     // interval between updates checks in seconds
define('CHECK_UPDATES_COUNT',    50);    // how many times to check for updates

?>
