<?php

/**
 * Game interface
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @todo       jQueryUI (css, draggable/droppable, effects (fade) when new ship)
 * @todo       showing changes (name, game, chat - how to know something has changed)
 *
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.php";;
require_once INCLUDE_PATH . "functions.php";
require_once INCLUDE_PATH . "BattleshipsClass.php";

// initiate Battleships object
$oBattleships = new Battleships();
$error = $oBattleships->getError();

if ($error !== null) {
    echo $error;
    exit;
}


// if no hash provided initiate a game and redirect to the game's hash
if (!array_key_exists('hash', $_GET)) {
    $game_initiate = $oBattleships->initGame();
    if ($game_initiate === false) {
        echo $oBattleships->getError();
        exit;
    }

    header("Location: " . $_SERVER['SCRIPT_URI'] . "?hash=" . $_SESSION['player_hash'], 303);
    exit;
}

// initiate the game and throw error when hash is incorrect
$game_initiate = $oBattleships->initGame($_GET['hash']);
if ($game_initiate === false) {
    echo $oBattleships->getError() . "<br />Refresh or try to <a href='" . $_SERVER['SCRIPT_URI'] . "'>start a new game</a>.";
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
    <script src="js/main.js"></script>
</head>
<body>
<?php
if (!is_root_dir() && (SQLITE_FILE == "battleships.sqlite")) {
    $direct_sqlite_file = get_direct_sqlite_url();
?>
    <div class="warning" style="width: 968px;">
        Game is not open in root directory and SQLITE_FILE has default set "<?=SQLITE_FILE?>" and
        is available directly!<br />
        <a href="<?=$direct_sqlite_file?>"><?=$direct_sqlite_file?></a><br />
        Set root URL for html directory or change SQLITE_FILE in config.php to a random one <br />
        (e.g. "<?=hash("sha256", $_SESSION['player_hash'] . microtime(true) . rand())?>.sqlite") and delete current file.
    </div>
<?php
}
?>
    <div class="menu">
        Game No. <?=$_SESSION['game_id']?>
        <button id="start">Start</button>
        <button id="update">Updates [OFF]</button>
        <button id="new_game">New Game</button>
        <button id="random_shot">Random Shot</button>
    </div>
    <div class="board_container">
        <div class="board_menu">
            <input type="text" value="<?=$_SESSION['player_name']?>" /><span id="name_update"><?=$_SESSION['player_name']?></span>
        </div>
<?=Battleships::createBoard()?>
    </div>
    <div class="board_container">
        <div class="board_menu">
            <span><?=$_SESSION['other_name']?></span>
        </div>
<?=Battleships::createBoard()?>
    </div>
    <div class="log"></div>

    <p id="game_link">
<?php
    if ($_SESSION['player_number'] == 1 && $_SESSION['other_joined'] === false) {
        echo "Temporary link to the game for the oponent: <span>" . $_SERVER['SCRIPT_URI'] . "?hash=" . $_SESSION['other_hash'] . "</span>";
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
</body>
</html>
