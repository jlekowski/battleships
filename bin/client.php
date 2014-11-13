<?php

/**
 * Client for PHP CLI
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Rest\Client;
use Battleships\CliView;

$oClient = new Client(REST_URL);
$oCliView = new CliView($oClient);
$oCliView->run();
