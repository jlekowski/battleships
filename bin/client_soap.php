<?php

/**
 * Client for PHP CLI
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Soap\ApiClient;
use Battleships\CliView;

$oApiClient = new ApiClient();
$oCliView = new CliView($oApiClient);
$oCliView->run();
