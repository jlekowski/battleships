<?php

/**
 * General functions
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 */

function utc_time() {
    return time() - date("Z");
}

function is_root_dir() {
    $urlInfo     = parse_url($_SERVER['SCRIPT_URI']);
    $urlPathInfo = pathinfo($urlInfo['path']);

    return $urlPathInfo['dirname'] == "/";
}

function get_direct_sqlite_url() {
    $urlInfo     = parse_url($_SERVER['SCRIPT_URI']);
    $urlPathInfo = pathinfo($urlInfo['path']);

    $dirInfo = explode('/', $urlPathInfo['dirname']);
    $last_index = count($dirInfo) - 1;
    if( ($urlPathInfo['basename'] == $urlPathInfo['basename']) && !array_key_exists('extension', $urlPathInfo) ) {
        $last_index += 1;
    }
    $dirInfo[$last_index] = "db";

    $direct_sqlite_url = $urlInfo['scheme'] . "://" . $urlInfo['host'] . implode('/', $dirInfo) . "/" . SQLITE_FILE;

    return $direct_sqlite_url;
}

?>