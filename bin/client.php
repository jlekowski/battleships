<?php

/**
 * Client for PHP CLI
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Rest\ApiClient;
use Battleships\CliView;

$oApiClient = new ApiClient(REST_URL);
$oCliView = new CliView($oApiClient);
$oCliView->run();
