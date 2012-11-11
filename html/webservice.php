<?php

/**
 * Webservice for game actions management
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 *
 */
ob_start();

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.php";
require_once INCLUDE_PATH . "functions.php";
require_once INCLUDE_PATH . "BattleshipsClass.php";

// if we want to use long calls
ini_set('max_execution_time', CHECK_UPDATES_TIMEOUT);

// at the moment $_GET['action'] is required for each call
if (!array_key_exists('action', $_GET)) {
    exit;
}

// default return
$return = array('error' => false, 'success' => false);

// initiate Battleships object
$oBattleships = new Battleships();

switch ($_GET['action']) {
    // first call to the whole battle (if it's not a new one)
    case 'get_battle':
        // set the timezone offset
        if (array_key_exists('timezone_offset', $_GET) && abs($_GET['timezone_offset']) <= 14) {
            $_SESSION['timezone_offset'] = $_GET['timezone_offset'] * 60 * 60;
        }

        $battle = Battleships::getBattle();
        $chats  = $oBattleships->getChats($_SESSION['game_id']);

        $return['success'] = array('battle' => $battle, 'chats' => $chats);
        break;

    // when player's name is updated
    case 'name_update':
        if (array_key_exists('player_name', $_GET) && $oBattleships->updateName($_GET['player_name'])) {
            $return['success'] = true;
        }
        else {
            $return['error'] = true;
        }

        break;

    // when a player press "Start" after setting ships
    case 'start_game':
        if (array_key_exists("ships", $_GET) && $oBattleships->startGame($_GET['ships'])) {
            $return['success'] = true;
        }
        else {
            $return['error'] = true;
        }

        break;

    // chat
    case 'chat':
        if (array_key_exists("text", $_GET) && $oBattleships->addChat($_GET['text'])) {
            $return['success'] = date("Y-m-d H:i:s", utc_time() + $_SESSION['timezone_offset']);
        }
        else {
            $return['error'] = $oBattleships->getError();
        }

        break;

    // when player shoots
    case 'shot':
        $shot = array_key_exists("coords", $_GET) ? $oBattleships->addShot($_GET['coords']) : false;

        if ($shot !== false) {
            $return['success'] = $shot;
        }
        else {
            $return['error'] = true;
        }

        break;

    // getting changes (actions from other player)
    case 'get_updates':
        // close session because of long calls and synchronous nature of PHP Sessions (AJAX calls are asynchronous)
        session_write_close();
        $check_updates = true;
        $i = 1;

        while ($check_updates) {
            $updates = $oBattleships->getUpdates();

            if ($updates === false) {
                $return['error'] = true;
                $check_updates = false;
            }
            // if no new updates
            else if (count($updates) == 0) {
                // if checking for updates limit is not reached, wait and check again
                if (++$i > CHECK_UPDATES_COUNT) {
                    $check_updates = false;
                }
                else {
                    sleep(CHECK_UPDATES_INTERVAL);
                }
            }
            else {
                $return['success'] = $updates;
                $check_updates = false;

                // when updates found write them to the Session
                session_start();
                $_SESSION = array_merge($_SESSION, Battleships::$sessionUpdates);
            }
        }

        break;
}

echo json_encode($return);
ob_end_flush();

?>