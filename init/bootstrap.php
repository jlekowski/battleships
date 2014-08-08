<?php

/**
 * Bootstrap
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.5
 *
 */

require_once 'config.php';

// PHP error reporting settings
error_reporting(E_ALL & ~E_STRICT); // set E_ALL for debugging

// error logging settings
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", LOG_PATH.LOG_FILE);

spl_autoload_register(function ($className) {
    $file = INCLUDE_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (!is_file($file)) {
        return false;
    }

    require $file;
});
