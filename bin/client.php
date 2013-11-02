<?php

/**
 * client for PHP CLI
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    CLI test
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.php";
require_once INCLUDE_PATH . "functions.php";

$oBattleshipsClient = new BattleshipsClient();
$oBattleshipsCliInterface = new BattleshipsCliInterface($oBattleshipsClient);
$oBattleshipsCliInterface->run();
