<?php
namespace Battleships\Game;

use Battleships\DB;
use Battleships\Misc;

/**
 * Battleships\Game\Manager class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @TODO       getBattle() - battleground details not working with the front-end
 * @TODO       instead of $updates['lastIdEvents'] store on the server side last given update
 * @TODO       in_array() instead of array_search()
 * @TODO       startGame() coords check to checkShips()?
 * @TODO       start_game event - true instead of ships?
 * @todo       maybe PDO exec instead of query
 * @todo       last shot and whose turn it is
 * @todo       generate hash, edit it in DB and redirect to the new (initGame)
 *
 *
 */
class Manager
{
    /**
     * Battleships\DB Class Object
     *
     * Example: object(Battleships\DB)#2 (0) { }
     *
     * @var Battleships\DB
     */
    protected $oDB;

    /**
     * Battleships\Game\Data Class Object
     *
     * Example: object(Battleships\Game\Data)#2 (20) { }
     *
     * @var Battleships\Game\Data
     */
    public $oData;

    /**
     * Error variable
     *
     * Example: Shot's coordinates are incorrect (A11)
     *
     * @var string
     */
    private $error;

    /**
     * Array with Y axis elements
     *
     * Example: array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
     *
     * @var array
     */
    public static $axisY = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");

    /**
     * Array with X axis elements
     *
     * Example: array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
     *
     * @var array
     */
    public static $axisX = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10");

    /**
     * Initiates PDO Object and creates DB tables if required
     *
     * @param Battleships\Game\Data $oData
     * @param Battleships\DB $oDB
     * @return void
     */
    public function __construct(Data $oData, DB $oDB)
    {
        $this->oDB = $oDB;
        $this->oData = $oData;

        if ($this->doTablesExist() === false && is_null($this->getError())) {
            $this->createTables();
        }
    }

    /**
     * Returns Errors
     *
     * @return string Error
     */
    public function getError()
    {
        return $this->oDB->getError() !== null ? $this->oDB->getError() : $this->error;
    }

    /**
     * Sets Errors
     *
     * @param string $error Error to be set
     *
     * @return void
     */
    protected function setError($error)
    {
        $this->error = $error;
    }

    /**
     * Creates DB tables (CREATE TABLE) - games, events
     *
     * @return void
     */
    private function createTables()
    {
        $query = array();

        // creating games table
        $query[] = "
            CREATE TABLE IF NOT EXISTS games (
                id             INTEGER PRIMARY KEY,
                player1_hash   TEXT,
                player1_name   TEXT,
                player1_ships  TEXT,
                player2_hash   TEXT,
                player2_name   TEXT,
                player2_ships  TEXT,
                timestamp      NUMERIC
            )
        ";

        // creating events table
        $query[] = "
            CREATE TABLE IF NOT EXISTS events (
                id           INTEGER PRIMARY KEY,
                game_id      INTEGER,
                player       INTEGER,
                event_type   TEXT,
                event_value  TEXT,
                timestamp    NUMERIC
            )
        ";

        foreach ($query as $value) {
            $this->oDB->query($value);
        }
    }

    /**
     * Checks whether all table exist or not
     *
     * @return bool Whether all table exist
     */
    private function doTablesExist()
    {
        $query = "SELECT COUNT(*) AS count FROM sqlite_master WHERE name IN ('games', 'events') AND type = 'table'";
        $result = $this->oDB->getFirst($query);

        return $result['count'] == 2;
    }

    /**
     * Initiates a game
     *
     * Either creates a new game or gets already created one if hash provided<br />
     * Loads Battleships\Game\Data values
     *
     * @param string $hash Game hash
     *
     * @return bool Whether game was initiated successfully
     */
    public function initGame($hash = "")
    {
        $game = $hash === "" ? $this->createGame() : $this->getGameByHash($hash);

        if ($game === false) {
            return false;
        }

        $this->oData->setIdGames($game['id']);

        $events        = $this->getEvents(array("shot", "join_game", "start_game"));
        $playerNumber  = $game['player_number'];
        $otherNumber   = $playerNumber == 1 ? 2 : 1;
        $playerPrefix  = "player" . $playerNumber;
        $otherPrefix   = "player" . $otherNumber;
        $shots         = array_key_exists('shot',        $events) ? $events['shot']       : array();
        $joined        = array_key_exists('join_game',   $events) ? $events['join_game']  : array();
        $started       = array_key_exists('start_game',  $events) ? $events['start_game'] : array();
        $playerShots   = array_key_exists($playerNumber, $shots)  ? $shots[$playerNumber] : "";
        $otherShots    = array_key_exists($otherNumber,  $shots)  ? $shots[$otherNumber]  : "";
        $playerJoined  = array_key_exists($playerNumber, $joined);
        $otherJoined   = array_key_exists($otherNumber,  $joined);
        $playerStarted = array_key_exists($playerNumber, $started);
        $otherStarted  = array_key_exists($otherNumber,  $started);

        $this->oData->setGameTimestamp($game['timestamp']);
        $this->oData->setPlayerNumber($playerNumber);
        $this->oData->setOtherNumber($otherNumber);
        $this->oData->setPlayerHash($game[$playerPrefix.'_hash']);
        $this->oData->setOtherHash($game[$otherPrefix.'_hash']);
        $this->oData->setPlayerName($game[$playerPrefix.'_name']);
        $this->oData->setOtherName($game[$otherPrefix.'_name']);
        $this->oData->setPlayerShips($game[$playerPrefix.'_ships']);
        $this->oData->setOtherShips($game[$otherPrefix.'_ships']);
        $this->oData->setPlayerShots($playerShots);
        $this->oData->setOtherShots($otherShots);
        $this->oData->setPlayerJoined($playerJoined);
        $this->oData->setOtherJoined($otherJoined);
        $this->oData->setPlayerStarted($playerStarted);
        $this->oData->setOtherStarted($otherStarted);
        $this->oData->setLastIdEvents($this->findLastIdEvents($otherNumber));

        if (!$this->oData->getPlayerJoined()) {
            $this->joinGame();
        }

        $this->determineWhoseTurn();

        return true;
    }

    /**
     * Gets existing game
     *
     * Gets existing game from DB by hash
     *
     * @param string $hash Game hash
     *
     * @return array|false Game row from DB or false on error
     */
    private function getGameByHash($hash)
    {
        // what when 2 hashes found?
        $query = "SELECT *, CASE WHEN player1_hash = :hash THEN 1 ELSE 2 END AS player_number
                  FROM games
                  WHERE player1_hash = :hash OR player2_hash = :hash";
        $result = $this->oDB->getFirst($query, array(':hash' => $hash));

        if (is_array($result) && empty($result)) {
            $this->setError("Game (" . $hash . ") does not exist");
            return false;
        }

        return $result;
    }

    /**
     * Creates a new game
     *
     * @return array|false New game row from DB or false on error
     */
    private function createGame()
    {
        $hash     = hash("md5", uniqid(mt_rand(), true));
        $temphash = hash("md5", uniqid(mt_rand(), true));

        // array with values to be inserted to the table
        $game = array(
            'player1_hash'  => $hash,
            'player1_name'  => "Player 1",
            'player1_ships' => "",
            'player2_hash'  => $temphash,
            'player2_name'  => "Player 2",
            'player2_ships' => "",
            'timestamp'     => Misc::getUtcTime()->getTimestamp()
        );

        $query = "INSERT INTO games (player1_hash, player1_name, player1_ships,
                                     player2_hash, player2_name, player2_ships, timestamp)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $result = $this->oDB->fQuery($query, array_values($game));

        if ($result === false) {
            return false;
        }

        $game['player_number'] = 1; // player who starts is always No. 1
        $game['id'] = $this->oDB->lastInsertId();

        return $game;
    }

    /**
     * Updates player's name
     *
     * @param string $playerName Player's new name
     *
     * @return bool Whether name was updated successfully
     */
    public function updateName($playerName)
    {
        $query = sprintf("UPDATE games SET player%d_name = ? WHERE id = ?", $this->oData->getPlayerNumber());
        $result = $this->oDB->fQuery($query, array($playerName, $this->oData->getIdGames()));

        if ($result === false) {
            return false;
        }

        $this->addEvent('name_update', $playerName);

        return true;
    }

    /**
     * Gets newest updates
     *
     * Gets updates which appeared since the last getUpdates() call<br />
     * array(0 => array('action' => event_type, 'value' => event_value))
     *
     * @return array|false List of updates or false on error
     */
    public function getUpdates()
    {
        $query = "SELECT * FROM events WHERE id > ? AND game_id = ? AND player = ?";
        $result = $this->oDB->fQuery($query, array(
            $this->oData->getLastIdEvents(),
            $this->oData->getIdGames(),
            $this->oData->getOtherNumber()
        ));

        if ($result === false) {
            return false;
        }

        $updates = array();
        foreach ($result as $value) {
            switch ($value['event_type']) {
                case "name_update":
                    $this->oData->setOtherName($value['event_value']);
                    break;

                case "start_game":
                    $this->oData->setOtherShips($value['event_value']);
                    break;

                case "shot":
                    $this->oData->appendOtherShots($value['event_value']);
                    break;
            }

            $lastIdEvents = max($this->oData->getLastIdEvents(), $value['id']);
            $this->oData->setLastIdEvents($lastIdEvents);

            if ($value['event_type'] == "chat") {
                $eventDate = new \DateTime("@" . $value['timestamp']);
                $eventValue = array(
                    'text' => $value['event_value'],
                    'time' => $eventDate->modify($this->oData->getTimezoneOffset() . "hour")->format("Y-m-d H:i:s")
                );
            } elseif ($value['event_type'] == 'start_game') {
                $eventValue = true;
            } else {
                $eventValue = $value['event_value'];
            }

            $updates[ $value['event_type'] ][] = $eventValue;
            $updates['lastIdEvents'] = array($lastIdEvents);
        }

        return $updates;
    }

    /**
     * Inserts new event to the DB
     *
     * Gets existing game from DB by hash
     *
     * @param string $eventType Type of the event
     * @param string $eventValue Value of the event
     *
     * @return bool Whether event was inserted successfully
     */
    private function addEvent($eventType, $eventValue = 1)
    {
        $query = "INSERT INTO events (game_id, player, event_type, event_value, timestamp) VALUES (?, ?, ?, ?, ?)";
        $result = $this->oDB->fQuery($query, array(
            $this->oData->getIdGames(),
            $this->oData->getPlayerNumber(),
            $eventType,
            $eventValue,
            Misc::getUtcTime()->getTimestamp()
        ));

        if ($result === false) {
            return false;
        }

        switch ($eventType) {
            case 'name_update':
                $this->oData->setPlayerName($eventValue);
                break;

            case 'join_game':
                $this->oData->setPlayerJoined($eventValue);
                break;

            case 'start_game':
                $this->oData->setPlayerShips($eventValue);
                break;

            case 'shot':
                $this->oData->appendPlayerShots($eventValue);
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * Starts the game
     *
     * Updates the game with the ships provided
     *
     * @param string $ships Ships set by the player (Example: "A1,B4,J10,..."))
     *
     * @return bool Whether the game started successfully
     */
    public function startGame($ships)
    {
        if (!self::checkShips($ships) || count($this->oData->getPlayerShips()) > 0) {
            return false;
        }

        // check whether all coordinates are correct (e.g. A1, B4, J10)
        $shipsArray = explode(",", $ships);
        foreach ($shipsArray as $coords) {
            if (self::coordsInfo($coords) === false) {
                $this->setError("Ship's coordinates are incorrect (" . $coords . ")");
                return false;
            }
        }

        $query = sprintf("UPDATE games SET player%d_ships = ? WHERE id = ?", $this->oData->getPlayerNumber());
        $result = $this->oDB->fQuery($query, array($ships, $this->oData->getIdGames()));

        if ($result === false) {
            return false;
        }

        $this->addEvent("start_game", $ships);

        return true;
    }

    /**
     * Adds a shot
     *
     * Check the coordinates of the shot and returns the result
     *
     * @param string $coords Shot coordinates (Example: "A1", "B4", "J10", ...)
     *
     * @return string Shot result (miss|sunk|hit)
     */
    public function addShot($coords)
    {
        if (self::coordsInfo($coords) === false) {
            $this->setError("Shot's coordinates are incorrect (" . $coords . ")");
            return false;
        }

        if ($this->oData->getOtherStarted() == false) {
            $this->setError("Other player has not started yet");
            return false;
        }

        if ($this->oData->isMyTurn() == false) {
            $this->setError("It's other player's turn");
            return false;
        }

        $this->addEvent("shot", $coords);

        // If other ship at these coordinates (if hit)
        if (array_search($coords, $this->oData->getOtherShips()) === false) {
            $result = "miss";
        } elseif ($this->checkSunk($coords)) {
            // If other ship is sunk after this hit
            $result = "sunk";
        } else {
            $result = "hit";
        }

        return $result;
    }

    /**
     * Checks if the shot sinks the ship
     *
     * Checks if all other masts has been hit
     *
     * @param string $coords Shot coordinates (Example: "A1", "B4", "J10", ...)
     * @param string $shooter Whose shot is about to be checked (player|other)
     * @param int $direction Direction which is checked for ship's masts
     *
     * @return bool Whether the ship is sunk after this shot or not
     */
    private function checkSunk($coords, $shooter = "player", $direction = null)
    {
        $coordsInfo = self::coordsInfo($coords);
        if ($coordsInfo === false) {
            $this->setError("Coordinates are incorrect (" . $coords . ")");
            return false;
        }

        if (!in_array($shooter, array("player", "other"))) {
            $this->setError("Incorrect shooter (" . $shooter . ")");
            return false;
        }

        $checkSunk = true;

        // neighbour coordinates, taking into consideration edge positions (A and J rows, 1 and 10 columns)
        $sunkCoords = array(
            $coordsInfo['position_y'] > 0 ? self::$axisY[$coordsInfo['position_y'] - 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_y'] < 9 ? self::$axisY[$coordsInfo['position_y'] + 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_x'] < 9 ? $coordsInfo['coord_y'] . self::$axisX[$coordsInfo['position_x'] + 1] : "",
            $coordsInfo['position_x'] > 0 ? $coordsInfo['coord_y'] . self::$axisX[$coordsInfo['position_x'] - 1] : ""
        );

        // try to find a mast which hasn't been hit
        foreach ($sunkCoords as $key => $value) {
            // if no coordinate on this side (end of the board) or direction is specified,
            // but it's not the specified one
            if ($value === "" || ($direction !== null && $direction !== $key)) {
                continue;
            }

            $ships = $shooter == "player" ? $this->oData->getOtherShips()  : $this->oData->getPlayerShips();
            $shots = $shooter == "player" ? $this->oData->getPlayerShots() : $this->oData->getOtherShots();
            $ship = array_search($value, $ships);
            $shot = array_search($value, $shots);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $checkSunk = $this->checkSunk($value, $shooter, $key);
            } elseif ($ship !== false) {
                // if mast hasn't been hit, the the ship can't be sunk
                $checkSunk = false;
            }


            if ($checkSunk === false) {
                break;
            }
        }

        return $checkSunk;
    }

    /**
     * Gets shots for the game
     *
     * Returns all shots for a requested game
     *
     * @return array Shots for the game per player (Example: [1 => ["A1", "B4", "J10"], 2 => ["C3", "F6", "I1"]]
     */
    public function getShots()
    {
        $events = $this->getEvents('shot');
        return array_key_exists('shot', $events) ? $events['shot'] : array();
    }

    /**
     * Gets chats for the game
     *
     * Returns all chats for a requested game
     *
     * @return array|false Chats for the game
     *         (Example: [0 => ['name' => {player_name}, 'text' => "Hi!", 'time' => "2012-11-05 23:34"],  ...])
     */
    public function getChats()
    {
        $chats = array();

        $result = $this->getEvents("chat", true);
        if ($result === false) {
            return false;
        }

        // raw events result requested to build a custom array with chats' details
        foreach ($result as $value) {
            $eventDate = new \DateTime("@" . $value['timestamp']);
            $chats[] = array(
                'name' => ($value['player'] == $this->oData->getPlayerNumber()
                    ? $this->oData->getPlayerName()
                    : $this->oData->getOtherName()),
                'text' => $value['event_value'],
                'time' => $eventDate->modify($this->oData->getTimezoneOffset() . "hour")->format("Y-m-d H:i:s")
            );
        }

        return $chats;
    }

    /**
     * Gets events for the game
     *
     * Returns all events of requested type for a requested game.
     * If not $event_type provided, returns all events.
     * If $raw no specified, groups results by event type and player number.
     *
     * @param string|array $eventType Types of events to be returned (all if not/null provided)
     * @param bool $raw Whether to return a query result or group the result by even_type and player_number
     *
     * @return array|false Events for the game
     *         (Example: ['chat' => [1 => [0 => "Hi!", 2 => ...], 'shot' => [1 => array[0 => "A1", ...], ...]
     */
    private function getEvents($eventType, $raw = false)
    {
        $query = "SELECT * FROM events WHERE game_id = ? AND event_type IN (:event_types)";
        $result = $this->oDB->fQuery($query, array($this->oData->getIdGames(), ':event_types' => $eventType));

        if ($raw || $result === false) {
            return $result;
        }

        // group the response by event type and player number
        $events = array();
        foreach ($result as $value) {
            $events[ $value['event_type'] ][ $value['player'] ][] = $value['event_value'];
        }

        return $events;
    }

    /**
     * Gets the last event id for the game
     *
     * Returns last even ip for a specified game and a specified player.
     *
     * @param int $player Player number (1|2)
     *
     * @return int|false Id of the last event made by the player
     */
    private function findLastIdEvents($player)
    {
        $query = "SELECT MAX(id) AS id FROM events WHERE game_id = ? AND player = ? GROUP BY game_id";
        $result = $this->oDB->getFirst($query, array($this->oData->getIdGames(), $player));

        if ($result === false) {
            return false;
        }

        return empty($result) ? 0 : (int)$result['id'];
    }

    /**
     * Get the current battle record (players' battlegrounds)
     *
     * Example: <br />
     * ['playerGround' => ['A1' => "miss", 'C4' => "hit", ...], 'otherGround' => ['J10' => "sunk", ...]]
     *
     * @return array
     */
    public function getBattle()
    {
        $battle = array('playerGround' => array(), 'otherGround' => array());
        $prefixes = array(array("player", "other"), array("other", "player"));

        $ships = array(
            'player' => $this->oData->getPlayerShips(),
            'other'  => $this->oData->getOtherShips()
        );

        $shots = array(
            'player' => $this->oData->getPlayerShots(),
            'other'  => $this->oData->getOtherShots()
        );

        foreach ($prefixes as $prefix) {
            foreach ($shots[ $prefix[0] ] as $value) {
                if (array_search($value, $ships[ $prefix[1] ]) === false) {
                    $shot = "miss";
                } elseif ($this->checkSunk($value, $prefix[0])) {
                    $shot = "sunk";
                } else {
                    $shot = "hit";
                }

                $battle[$prefix[1].'Ground'][$value] = $shot;
            }
        }

        foreach ($ships['player'] as $ship) {
            if (!array_key_exists($ship, $battle['playerGround'])) {
                $battle['playerGround'][$ship] = "ship";
            }
        }

        return $battle;
    }

    /**
     * Adds a chat
     *
     * Adds new 'chat' event
     *
     * @param string $text Chat text
     *
     * @return bool Whether 'chat' event was inserted successfully
     */
    public function addChat($text)
    {
        return $this->addEvent("chat", $text);
    }

    /**
     * Marks that a player joined current game
     *
     * Adds new 'join_game' event
     *
     * @return bool Whether 'join_game' event was inserted successfully
     */
    public function joinGame()
    {
        return $this->addEvent("join_game");
    }

    /**
     * Converts standard coordinates to board index (numeric value)
     *
     * Standard coordinates are converted to indexes, e.g. "A1" -> "00", "B3" -> "12", "J10" -> "99"
     *
     * @param string $coords Coordinates (Example: "A1", "B4", "J10", ...)
     *
     * @return string Two indexes (Y and X) concatenated
     */
    private static function toIndex($coords)
    {
        $coordsInfo = self::coordsInfo($coords);
        if ($coordsInfo === false) {
            return false;
        }

        return $coordsInfo['position_y'] . $coordsInfo['position_x'];
    }

    /**
     * Gives the detailed information about the coordinates
     *
     * Standard coordinates are split into Y and X axis values and appended with the index information.
     *
     * Example: "B3" -> array('coord_y' => "B", 'coord_x' => "3", 'position_y' => 1, 'position_x' => 2)
     *
     * @param string $coords Coordinates (Example: "A1", "B4", "J10", ...)
     *
     * @return array Split coordinates (Y and X) and indexes (Y and X)
     */
    private static function coordsInfo($coords)
    {
        if (!$coords) {
            return false;
        }

        $coordY    = $coords[0];
        $coordX    = substr($coords, 1);

        $positionY = array_search($coordY, self::$axisY);
        $positionX = array_search($coordX, self::$axisX);

        if ($positionY === false || $positionX === false) {
            return false;
        }


        $coordsInfo = array(
            'coord_y'    => $coordY,
            'coord_x'    => $coordX,
            'position_y' => $positionY,
            'position_x' => $positionX
        );

        return $coordsInfo;
    }

    /**
     * Checks if ships are set correctly
     *
     * Validates coordinates of all ships' masts, checks the number,
     *     sizes and shapes of the ships, and potential edge connections between them.
     *
     * @param string $ships Ships set by the player (Example: "A1,B4,J10,...")
     *
     * @return bool Whether the ships are set correctly
     */
    public static function checkShips($ships)
    {
        // converts all coordinates to indexes and sorts them for more efficient validation
        $shipsArray = array_map("self::toIndex", explode(",", $ships));
        sort($shipsArray);

        // required number of masts
        $shipsLength = 20;
        // sizes of ships to be count
        $shipsTypes  = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        // B3 (index 12), going 2 down and 3 left is D6 (index 35), so 12 + (2 * 10) + (3 * 1) = 35
        $directionMultipliers = array(1, 10);

        // if the number of masts is correct
        if (count($shipsArray) != $shipsLength) {
            return false;
        }


        // check if no edge connection
        foreach ($shipsArray as $key => $index) {
            if ($index[0] == 9) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), upper right corner is A4 (index 03), so 12 - 3 = 9 -
            // second digit 0 is first row, so no upper corner
            $upperRightCorner = ($index[1] > 0) && (in_array($index + 9, $shipsArray));
            // B3 (index 12), lower right corner is C4 (index 23), so 23 - 12 = 11 -
            // second digit 9 is last row, so no lower corner
            $lowerRightCorner = ($index[1] < 9) && (in_array($index + 11, $shipsArray));

            if ($upperRightCorner || $lowerRightCorner) {
                return false;
            }
        }

        $masts = array();

        // check if there are the right types of ships
        foreach ($shipsArray as $key => $index) {
            // we ignore masts which have already been marked as a part of a ship
            if (array_key_exists($index, $masts)) {
                continue;
            }

            foreach ($directionMultipliers as $k => $multiplier) {
                $axisIndex   = $k == 1 ? 0 : 1;
                $boardOffset = $index[$axisIndex];

                $shipType = 1;
                // check for masts until the battleground border is reached
                while ($boardOffset + $shipType <= 9) {
                    $checkIndex = sprintf("%02s", $index + ($shipType * $multiplier));

                    // no more masts
                    if (!in_array($checkIndex, $shipsArray)) {
                        break;
                    }

                    // mark the mast as already checked
                    $masts[$checkIndex] = true;

                    // ship is too long
                    if (++$shipType > 4) {
                        return false;
                    }
                }

                // if not masts found and more directions to check
                if (($shipType == 1) && ($k + 1 != count($directionMultipliers))) {
                    continue;
                }

                break; // either all (both) directions checked or the ship is found
            }

            $shipsTypes[$shipType]++;
        }

        // whether the number of different ship types is correct
        $diff = array_diff_assoc($shipsTypes, array(1 => 4, 2 => 3, 3 => 2, 4 => 1));
        if (!empty($diff)) {
            return false;
        }

        return true;
    }

    /**
     * Finds and sets which player's turn it is
     *
     * @return bool Whether turn determined
     */
    private function determineWhoseTurn()
    {
        $query = "SELECT player, event_value FROM events
                  WHERE game_id = ? AND event_type = 'shot' ORDER BY id DESC LIMIT 1";
        $result = $this->oDB->getFirst($query, array($this->oData->getIdGames()));

        if ($result === false) {
            return false;
        }

        if (empty($result)) {
            $whoseTurn = 1;
        } elseif ($result['player'] == $this->oData->getPlayerNumber()) {
            $whoseTurn = in_array($result['event_value'], $this->oData->getOtherShips())
                ? $this->oData->getPlayerNumber() : $this->oData->getOtherNumber();
        } else {
            $whoseTurn = in_array($result['event_value'], $this->oData->getPlayerShips())
                ? $this->oData->getOtherNumber() : $this->oData->getPlayerNumber();
        }

        $this->oData->setWhoseTurn($whoseTurn);

        return true;
    }

    /**
     * Creates battleground HTML
     *
     * Return HTML for the 10x10 battleground board (div board class and divs in it) with A B C / 1 2 3 labels for axis.
     *
     * @return string Board HTML
     */
    public static function createBoard()
    {
        $board = "<div class='board'>\n";

        // 11 rows (first row for X axis labels)
        for ($i = 0; $i < 11; $i++) {
            $board .= "  <div>\n";

            // 11 divs/column in each row (first column for Y axis labels)
            for ($j = 0; $j < 11; $j++) {
                if ($i == 0 && $j > 0) {
                    $text = self::$axisY[($j - 1)];
                } elseif ($j == 0 && $i > 0) {
                    $text = self::$axisX[($i - 1)];
                } else {
                    $text = "";
                }

                $board .= "    <div>" . $text . "</div>\n";
            }

            $board .= "  </div>\n";
        }

        $board .= "</div>";

        return $board;
    }
}
