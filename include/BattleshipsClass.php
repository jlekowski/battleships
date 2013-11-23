<?php

/**
 * Battleships class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.3
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
class Battleships
{

    /**
     * DB Class Object
     *
     * Example: object(PDO)#2 (0) { }
     *
     * @var DB
     */
    protected $oDB;

    /**
     * BattleshipsGame Class Object
     *
     * Example: object(BattleshipsGame)#3 (16) { }
     *
     * @var BattleshipsGame
     */
    public $oBattleshipsGame;

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
    public static $axisX = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

    /**
     * Initiates PDO Object and creates DB tables if required
     *
     * @param BattleshipsGame $oBattleshipsGame
     * @param DB $oDB
     * @return void
     */

    public function __construct(BattleshipsGame $oBattleshipsGame, DB $oDB)
    {
        $this->oDB = $oDB;
        $this->oBattleshipsGame = $oBattleshipsGame;

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
     * Loads BattleshipsGame values
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

        $this->oBattleshipsGame->setIdGames($game['id']);

        $events         = $this->getEvents(array("shot", "join_game", "start_game"));
        $player_number  = $game['player_number'];
        $other_number   = $player_number == 1 ? 2 : 1;
        $player_prefix  = "player" . $player_number;
        $other_prefix   = "player" . $other_number;
        $shots          = array_key_exists('shot',         $events) ? $events['shot']        : array();
        $joined         = array_key_exists('join_game',    $events) ? $events['join_game']   : array();
        $started        = array_key_exists('start_game',   $events) ? $events['start_game']  : array();
        $player_shots   = array_key_exists($player_number, $shots)  ? $shots[$player_number] : "";
        $other_shots    = array_key_exists($other_number,  $shots)  ? $shots[$other_number]  : "";
        $player_joined  = array_key_exists($player_number, $joined);
        $other_joined   = array_key_exists($other_number,  $joined);
        $player_started = array_key_exists($player_number, $started);
        $other_started  = array_key_exists($other_number,  $started);

        $this->oBattleshipsGame->setGameTimestamp($game['timestamp']);
        $this->oBattleshipsGame->setPlayerNumber($player_number);
        $this->oBattleshipsGame->setOtherNumber($other_number);
        $this->oBattleshipsGame->setPlayerHash($game[$player_prefix.'_hash']);
        $this->oBattleshipsGame->setOtherHash($game[$other_prefix.'_hash']);
        $this->oBattleshipsGame->setPlayerName($game[$player_prefix.'_name']);
        $this->oBattleshipsGame->setOtherName($game[$other_prefix.'_name']);
        $this->oBattleshipsGame->setPlayerShips($game[$player_prefix.'_ships']);
        $this->oBattleshipsGame->setOtherShips($game[$other_prefix.'_ships']);
        $this->oBattleshipsGame->setPlayerShots($player_shots);
        $this->oBattleshipsGame->setOtherShots($other_shots);
        $this->oBattleshipsGame->setPlayerJoined($player_joined);
        $this->oBattleshipsGame->setOtherJoined($other_joined);
        $this->oBattleshipsGame->setPlayerStarted($player_started);
        $this->oBattleshipsGame->setOtherStarted($other_started);
        $this->oBattleshipsGame->setLastIdEvents($this->findLastIdEvents($other_number));

        if (!$this->oBattleshipsGame->getPlayerJoined()) {
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
            'timestamp'     => utc_time()
        );

        $query = "INSERT INTO games (player1_hash, player1_name, player1_ships, player2_hash, player2_name, player2_ships, timestamp)
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
     * @param string $player_name Player's new name
     *
     * @return bool Whether name was updated successfully
     */
    public function updateName($player_name)
    {
        $query = sprintf("UPDATE games SET player%d_name = ? WHERE id = ?", $this->oBattleshipsGame->getPlayerNumber());
        $result = $this->oDB->fQuery($query, array($player_name, $this->oBattleshipsGame->getIdGames()));

        if ($result === false) {
            return false;
        }

        $this->addEvent('name_update', $player_name);

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
            $this->oBattleshipsGame->getLastIdEvents(),
            $this->oBattleshipsGame->getIdGames(),
            $this->oBattleshipsGame->getOtherNumber()
        ));

        if ($result === false) {
            return false;
        }

        $updates = array();
        foreach ($result as $key => $value) {
            switch ($value['event_type']) {
                case "name_update":
                    $this->oBattleshipsGame->setOtherName($value['event_value']);
                    break;

                case "start_game":
                    $this->oBattleshipsGame->setOtherShips($value['event_value']);
                    break;

                case "shot":
                    $this->oBattleshipsGame->appendOtherShots($value['event_value']);
                    break;
            }

            $lastIdEvents = max($this->oBattleshipsGame->getLastIdEvents(), $value['id']);
            $this->oBattleshipsGame->setLastIdEvents($lastIdEvents);

            if ($value['event_type'] == "chat") {
                $event_value = array(
                    'text' => $value['event_value'],
                    'time' => date("Y-m-d H:i:s", $value['timestamp'] + $this->oBattleshipsGame->getTimezoneOffset())
                );
            } else if ($value['event_type'] == 'start_game') {
                $event_value = true;
            } else {
                $event_value = $value['event_value'];
            }

            $updates[ $value['event_type'] ][] = $event_value;
            $updates['lastIdEvents'] = array($lastIdEvents);
        }

        return $updates;
    }

    /**
     * Inserts new event to the DB
     *
     * Gets existing game from DB by hash
     *
     * @param string $event_type Type of the event
     * @param string $event_value Value of the event
     *
     * @return bool Whether event was inserted successfully
     */
    private function addEvent($event_type, $event_value = 1)
    {
        $query = "INSERT INTO events (game_id, player, event_type, event_value, timestamp) VALUES (?, ?, ?, ?, ?)";
        $result = $this->oDB->fQuery($query, array($this->oBattleshipsGame->getIdGames(), $this->oBattleshipsGame->getPlayerNumber(), $event_type, $event_value, utc_time()));

        if ($result === false) {
            return false;
        }

        switch ($event_type) {
            case 'name_update':
                $this->oBattleshipsGame->setPlayerName($event_value);
                break;

            case 'join_game':
                $this->oBattleshipsGame->setPlayerJoined($event_value);
                break;

            case 'start_game':
                $this->oBattleshipsGame->setPlayerShips($event_value);
                break;

            case 'shot':
                $this->oBattleshipsGame->appendPlayerShots($event_value);
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
        if (!self::checkShips($ships) || count($this->oBattleshipsGame->getPlayerShips()) > 0) {
            return false;
        }

        // check whether all coordinates are correct (e.g. A1, B4, J10)
        $ships_array = explode(",", $ships);
        foreach ($ships_array as $coords) {
            if (self::coordsInfo($coords) === false) {
                $this->setError("Ship's coordinates are incorrect (" . $coords . ")");
                return false;
            }
        }

        $query = sprintf("UPDATE games SET player%d_ships = ? WHERE id = ?", $this->oBattleshipsGame->getPlayerNumber());
        $result = $this->oDB->fQuery($query, array($ships, $this->oBattleshipsGame->getIdGames()));

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

        if ($this->oBattleshipsGame->getOtherStarted() == false) {
            $this->setError("Other player has not started yet");
            return false;
        }

        if ($this->oBattleshipsGame->isMyTurn() == false) {
            $this->setError("It's other player's turn");
            return false;
        }

        $this->addEvent("shot", $coords);

        // If other ship at these coordinates (if hit)
        if (array_search($coords, $this->oBattleshipsGame->getOtherShips()) === false) {
            $result = "miss";
        } else if ($this->checkSunk($coords)) {
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

        $check_sunk = true;

        // neighbour coordinates, taking into consideration edge positions (A and J rows, 1 and 10 columns)
        $sunk_coords = array(
            $coordsInfo['position_y'] > 0 ? self::$axisY[$coordsInfo['position_y'] - 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_y'] < 9 ? self::$axisY[$coordsInfo['position_y'] + 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_x'] < 9 ? $coordsInfo['coord_y'] . self::$axisX[$coordsInfo['position_x'] + 1] : "",
            $coordsInfo['position_x'] > 0 ? $coordsInfo['coord_y'] . self::$axisX[$coordsInfo['position_x'] - 1] : ""
        );

        // try to find a mast which hasn't been hit
        foreach ($sunk_coords as $key => $value) {
            // if no coordinate on this side (end of the board) or direction is specified, but it's not the specified one
            if ($value === "" || ($direction !== null && $direction !== $key)) {
                continue;
            }

            $ships = $shooter == "player" ? $this->oBattleshipsGame->getOtherShips()  : $this->oBattleshipsGame->getPlayerShips();
            $shots = $shooter == "player" ? $this->oBattleshipsGame->getPlayerShots() : $this->oBattleshipsGame->getOtherShots();
            $ship = array_search($value, $ships);
            $shot = array_search($value, $shots);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $check_sunk = $this->checkSunk($value, $shooter, $key);
            } else if ($ship !== false) {
                // if mast hasn't been hit, the the ship can't be sunk
                $check_sunk = false;
            }


            if ($check_sunk === false) {
                break;
            }
        }

        return $check_sunk;
    }

    /**
     * Gets shots for the game
     *
     * Returns all shots for a requested game
     *
     * @return array Shots for the game per player (Example: array(1 => array("A1", "B4", "J10"), 2 => array("C3", "F6", "I1"))
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
     * @return array|false Chats for the game (Example: array(0 => array('name' => {player_name}, 'text' => "Hi!", 'time' => "2012-11-05 23:34"),  ...))
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
            $chats[] = array(
                'name' => ($value['player'] == $this->oBattleshipsGame->getPlayerNumber()
                    ? $this->oBattleshipsGame->getPlayerName()
                    : $this->oBattleshipsGame->getOtherName()),
                'text' => $value['event_value'],
                'time' => date("Y-m-d H:i:s", $value['timestamp'] + $this->oBattleshipsGame->getTimezoneOffset())
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
     * @param string|array $event_type Types of events to be returned (all if not/null provided)
     * @param bool $raw Whether to return a query result or group the result by even_type and player_number
     *
     * @return array|false Events for the game (Example: array('chat' => array(1 => array(0 => "Hi!", 2 => ...), 'shot' => array(1 => array(0 => "A1", ...), ...)
     */
    private function getEvents($event_type, $raw = false)
    {
        $query = "SELECT * FROM events WHERE game_id = ? AND event_type IN (:event_types)";
        $result = $this->oDB->fQuery($query, array($this->oBattleshipsGame->getIdGames(), ':event_types' => $event_type));

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
        $result = $this->oDB->getFirst($query, array($this->oBattleshipsGame->getIdGames(), $player));

        if ($result === false) {
            return false;
        }

        return empty($result) ? 0 : (int)$result['id'];
    }

    /**
     * Gets the current battle run and player's ships
     *
     * Returns the battle run and player ships' coordinates based on players' shots, grouping by player and shot with the shot result.
     *
     * Example: array('player_ships' => array("J10", "F5", ...),<br />
     *    'player_shots' => array('A1' => "miss", 'C4' => "hit", ...),<br />
     *    'other_shots' => array('J10' => "sunk", ...))
     *
     * @return array Battle run and players' ships
     */
    public function getBattle()
    {
        $battle = array('playerGround' => array(), 'otherGround' => array());
        $prefixes = array(array("player", "other"), array("other", "player"));

        $ships = array(
            'player' => $this->oBattleshipsGame->getPlayerShips(),
            'other'  => $this->oBattleshipsGame->getOtherShips()
        );

        $shots = array(
            'player' => $this->oBattleshipsGame->getPlayerShots(),
            'other'  => $this->oBattleshipsGame->getOtherShots()
        );

        foreach ($prefixes as $prefix) {
            foreach ($shots[ $prefix[0] ] as $value) {
                if (array_search($value, $ships[ $prefix[1] ]) === false) {
                    $shot = "miss";
                } else if ($this->checkSunk($value, $prefix[0])) {
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

        $coord_y    = $coords[0];
        $coord_x    = substr($coords, 1);

        $position_y = array_search($coord_y, self::$axisY);
        $position_x = array_search($coord_x, self::$axisX);

        if ($position_y === false || $position_x === false) {
            return false;
        }


        $coordsInfo = array(
            'coord_y'    => $coord_y,
            'coord_x'    => $coord_x,
            'position_y' => $position_y,
            'position_x' => $position_x
        );

        return $coordsInfo;
    }

    /**
     * Checks if ships are set correctly
     *
     * Validates coordinates of all ships' masts, checks the number, sizes and shapes of the ships, and potential edge connections between them.
     *
     * @param string $ships Ships set by the player (Example: "A1,B4,J10,...")
     *
     * @return bool Whether the ships are set correctly
     */
    public static function checkShips($ships)
    {
        // converts all coordinates to indexes and sorts them for more efficient validation
        $ships_array = array_map("self::toIndex", explode(",", $ships));
        sort($ships_array);

        // required number of masts
        $ships_length = 20;
        // sizes of ships to be count
        $ships_types  = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        // B3 (index 12), going 2 down and 3 left is D6 (index 35), so 12 + (2 * 10) + (3 * 1) = 35
        $direction_multipliers = array(1, 10);

        // if the number of masts is correct
        if (count($ships_array) != $ships_length) {
            return false;
        }


        // check if no edge connection
        foreach ($ships_array as $key => $index) {
            if ($index[0] == 9) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), upper right corner is A4 (index 03), so 12 - 3 = 9 - second digit 0 is first row, so no upper corner
            $upper_right_corner = ($index[1] > 0) && (in_array($index + 9, $ships_array));
            // B3 (index 12), lower right corner is C4 (index 23), so 23 - 12 = 11 - second digit 9 is last row, so no lower corner
            $lower_right_corner = ($index[1] < 9) && (in_array($index + 11, $ships_array));

            if ($upper_right_corner || $lower_right_corner) {
                return false;
            }
        }

        $masts = array();

        // check if there are the right types of ships
        foreach ($ships_array as $key => $index) {
            // we ignore masts which have already been marked as a part of a ship
            if (array_key_exists($index, $masts)) {
                continue;
            }

            foreach ($direction_multipliers as $k => $multiplier) {
                $axis_index   = $k == 1 ? 0 : 1;
                $board_offset = $index[$axis_index];

                $ship_type = 1;
                // check for masts until the battleground border is reached
                while ($board_offset + $ship_type <= 9) {
                    $check_index = sprintf("%02s", $index + ($ship_type * $multiplier));

                    // no more masts
                    if (!in_array($check_index, $ships_array)) {
                        break;
                    }

                    // mark the mast as already checked
                    $masts[$check_index] = true;

                    // ship is too long
                    if (++$ship_type > 4) {
                        return false;
                    }
                }

                // if not masts found and more directions to check
                if (($ship_type == 1) && ($k + 1 != count($direction_multipliers))) {
                    continue;
                }

                break; // either all (both) directions checked or the ship is found
            }

            $ships_types[$ship_type]++;
        }

        // whether the number of different ship types is correct
        $diff = array_diff_assoc($ships_types, array(1 => 4, 2 => 3, 3 => 2, 4 => 1));
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
        $query = "SELECT player, event_value FROM events WHERE game_id = ? AND event_type = 'shot' ORDER BY id DESC LIMIT 1";
        $result = $this->oDB->getFirst($query, array($this->oBattleshipsGame->getIdGames()));

        if ($result === false) {
            return false;
        }

        if (empty($result)) {
            $whoseTurn = 1;
        } else if ($result['player'] == $this->oBattleshipsGame->getPlayerNumber()) {
            $whoseTurn = in_array($result['event_value'], $this->oBattleshipsGame->getOtherShips())
                ? $this->oBattleshipsGame->getPlayerNumber() : $this->oBattleshipsGame->getOtherNumber();
        } else {
            $whoseTurn = in_array($result['event_value'], $this->oBattleshipsGame->getPlayerShips())
                ? $this->oBattleshipsGame->getOtherNumber() : $this->oBattleshipsGame->getPlayerNumber();
        }

        $this->oBattleshipsGame->setWhoseTurn($whoseTurn);

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
                } else if ($j == 0 && $i > 0) {
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
