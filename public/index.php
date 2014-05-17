<?php

/**
 * Game interface
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @todo       jQueryUI (css, draggable/droppable, effects (fade) when new ship)
 * @todo       showing changes (name, game, chat - how to know something has changed)
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\DB;
use Battleships\Game\Data;
use Battleships\Game\Manager;
use Battleships\Misc;

// initiate Battleships objects
$oDB = new DB(DB_TYPE);
$oData = new Data();
$oManager = new Manager($oData, $oDB);
$error = $oManager->getError();

if ($error !== null) {
    echo $error;
    exit;
}


// if no hash provided initiate a game and redirect to the game's hash
if (!array_key_exists('hash', $_GET)) {
    $gameInitiated = $oManager->initGame();
    if ($gameInitiated === false) {
        echo $oManager->getError();
        exit;
    }

    header("Location: " . $_SERVER['SCRIPT_URI'] . "?hash=" . $oManager->oData->getPlayerHash(), 303);
    exit;
}

// initiate the game and throw error when hash is incorrect
$gameInitiated = $oManager->initGame($_GET['hash']);
if ($gameInitiated === false) {
    echo $oManager->getError() . "<br />Refresh or try to <a href='" . $_SERVER['SCRIPT_URI'] . "'>start a new game</a>.";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Battleships</title>
    <link href="css/main.css" rel="stylesheet" />
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
    <script src="js/jquery.xml2json.js"></script>
    <script src="js/jquery.soap.js"></script>
    <script src="js/main.js"></script>
</head>
<body>
<?php
if (!Misc::isRootDir() && (SQLITE_FILE == "battleships.sqlite")) {
    $sqliteUrl = Misc::getSqliteUrl();
?>
    <div class="warning" style="width: 968px;">
        Game is not open in root directory and SQLITE_FILE has default set "<?=SQLITE_FILE?>" and
        is available directly!<br />
        <a href="<?=$sqliteUrl?>"><?=$sqliteUrl?></a><br />
        Set root URL for public directory or change SQLITE_FILE in config.php to a random one <br />
        (e.g. "<?=hash("sha256", uniqid(mt_rand(), true))?>.sqlite") and delete current file.
    </div>
<?php
}
?>
    <div class="menu">
        Game No. <?=$oManager->oData->getIdGames()?>
        <button id="start">Start</button>
        <button id="update">Updates [OFF]</button>
        <button id="new_game">New Game</button>
        <button id="random_ships">Random Ships</button>
        <button id="random_shot">Random Shot</button>
    </div>
    <div class="board_container">
        <div class="board_menu">
            <input type="text" value="<?=$oManager->oData->getPlayerName()?>" />
            <span id="name_update"><?=Misc::escapeString($oManager->oData->getPlayerName())?></span>
        </div>
<?=Manager::createBoard()?>
    </div>
    <div class="board_container">
        <div class="board_menu">
            <span><?=Misc::escapeString($oManager->oData->getOtherName())?></span>
        </div>
<?=Manager::createBoard()?>
    </div>
    <div class="log"></div>

    <p id="game_link">
<?php
if ($oManager->oData->getPlayerNumber() == 1 && $oManager->oData->getOtherJoined() === false) {
?>
    Temporary link to the game for the opponent:
    <span><?=$_SERVER['SCRIPT_URI']?>?hash=<?=$oManager->oData->getOtherHash()?></span>
<?php
}
?>
    </p>

    <div id="chatbox">
        <div class="chats"></div>
        <div class="prompt">
            <p>&gt;</p>
            <input type="text" />
        </div>
    </div>

    <input type="hidden" id="hash" value="<?=$oManager->oData->getPlayerHash()?>" />
    <input type="hidden" id="playerNumber" value="<?=$oManager->oData->getPlayerNumber()?>" />
</body>
</html>
