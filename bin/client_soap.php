<?php

/**
 * Client for PHP CLI
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Soap\Client;
use Battleships\CliView;

$oClient = new Client();
$oCliView = new CliView($oClient);
$oCliView->run();
