<?php

/**
 * Battleships class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @todo       sprintf or something with inserts
 * @todo       maybe PDO exec instead of query
 * @todo       getGame() not nice prefix and method name
 * @todo       game_id as an argument or from $_SESSION ?
 * @todo       last shot and whose turn it is
 * @todo       code formatting change
 * @todo       htmlentities before data insert not necessarily the best idea
 * @todo       generate hash, edit it in DB and redirect to the new (initGame)
 *
 *
 */
class Battleships
{

    /**
     * PDO Object
     *
     * Example: object(PDO)#2 (0) { }
     *
     * @var object
     */
    private $_DB;

    /**
     * PDO Statement Object
     *
     * Example: object(PDOStatement)#3 (1) {'queryString' => "SELECT ..."}
     *
     * @var object
     */
    private $_sth;

    /**
     * Error variable
     *
     * Example: HY000 | 8 | attempt to write a readonly database
     *
     * @var string
     */
    private $_error;

    /**
     * Array with Y axis elements
     *
     * Example: array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
     *
     * @var array
     */
    private static $_axisY = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");

    /**
     * Array with X axis elements
     *
     * Example: array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
     *
     * @var array
     */
    private static $_axisX = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

    /**
     * Used to store $_SESSION values when session is stopped
     *
     * Example: array('other_name' => "Player 2", 'last_event' => 142);
     *
     * @var array
     */
    public static $sessionUpdates = array();


    /**
     * Initiates PDO Object and creates DB tables if required
     *
     * @return void
     */
    public function __construct()
    {
        $file_exists = file_exists(SQLITE_PATH . SQLITE_FILE);

        try {
            $this->_DB = new PDO("sqlite:" . SQLITE_PATH . SQLITE_FILE);
        }
        catch (PDOException $e) {
            $this->_setError($e->getMessage());
        }

        if ($file_exists === false && is_null($this->getError())) {
            $this->_createTables();
        }
    }

    /**
     * Returns Errors
     *
     * @return string Error
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Sets Errors
     *
     * @param string $error Error to be set
     *
     * @return void
     */
    private function _setError($error)
    {
        $this->_error = $error;
    }

    /**
     * Sets DB Error variable
     *
     * Gets PDO errorInfo() result (array) and tranposes it into string
     *
     * @return void
     */
    private function _setDbError()
    {
        $error = implode(" | ", $this->_DB->errorInfo());
        $this->_setError($error);
    }

    /**
     * Runs SQL Query and returns result (all records)
     *
     * @param string $query SQL Query
     *
     * @return array|false SQL Query result
     */
    protected function _query($query)
    {
        $result = $this->_DB->query($query);

        if ($result === false) {
            $this->_setDbError();
            return false;
        }

        return $result->fetchall(PDO::FETCH_ASSOC);
    }

    /**
     * Sets query using PDO prepare
     *
     * @param string $query SQL Query prepare
     *
     * @return bool Whether an error in PDO prepare ocurred
     */
    protected function _prepare($query)
    {
        $this->_sth = $this->_DB->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

        if ($this->_sth === false) {
            $this->_setDbError();
            return false;
        }

        return true;
    }

    /**
     * Executes query using PDO execute
     *
     * @param array $values Values for SQL set in PDO prepare
     *
     * @return array|false SQL Query result
     */
    protected function _execute($values)
    {
        $result = $this->_sth->execute($values);

        if ($result === false) {
            $this->_setDbError();
            return false;
        }

        return $this->_sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executes query with array values (for IN clause)
     *
     * Allows to set array values for IN clause, e.g. WHERE id = :id | array(':id' => array(1,5,6))
     *
     * @param string $query SQL Query prepare
     * @param array $parameters Values for SQL set in PDO prepare
     *
     * @return array|false SQL Query result
     */
    protected function _fQuery($query, $parameters)
    {
        // if array is multidimensional, i.e. an array is used for IN clause - otherwise simple prepare and execute in one method
        if (count($parameters) != count($parameters, COUNT_RECURSIVE)) {
            foreach ($parameters as $key => $value) {
                // values must be reorganised only for array values
                if (!is_array($value)) {
                    continue;
                }
                // for array values named marker must be used, i.e. :id, :event, ...
                if (is_integer($key)) {
                    $this->_setError("DB query error - array PDO values must be set in assiociative array");
                    return false;
                }

                $markers = array();
                foreach ($value as $k => $v) {
                    $markers[] = $key.$k; // e.g. ':id0', ':id1', ...
                    $parameters[ $key.$k ] = $v;
                }

                $query = str_replace($key, implode(",", $markers), $query);
                unset($parameters[$key]); // to replace :id with :id0, :id1, ...
            }
        }

        $this->_prepare($query);

        return $this->_execute($parameters);
    }

    /**
     * Creates DB tables (CREATE TABLE) - games, events
     *
     * @return void
     */
    private function _createTables()
    {
        $query = array();

        // creating games table
        $query[] = "
            CREATE TABLE games (
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
            CREATE TABLE events (
                id           INTEGER PRIMARY KEY,
                game_id      INTEGER,
                player       INTEGER,
                event_type   TEXT,
                event_value  TEXT,
                timestamp    NUMERIC
            )
        ";

        foreach ($query as $value) {
            $this->_query($value);
        }
    }

//    /**
//     * Escape the text before inserting it to SQL Query
//     *
//     * Runs native escaping function, converts html special chars and removes html tags
//     *
//     * @param string $text Text to be escaped
//     * @param bool $esc_quotes Whether to escape quotes or not
//     *
//     * @return string Escaped text
//     */
//    private function _escapeString($text, $esc_quotes = true)
//    {
//        // don't escape serialized string (" -> &quot;)
//        $flags = $esc_quotes ? ENT_COMPAT : ENT_NOQUOTES;
//
//        return $this->_DB->quote( htmlentities(strip_tags($text), $flags) );
//    }

    /**
     * Initiates a game
     *
     * Either creates a new game or gets already created one if hash provided<br />
     * Sets appropriate $_SESSION values
     *
     * @param string $hash Game hash
     *
     * @return bool Whether game was initiated successfully
     */
    public function initGame($hash = null)
    {
        $_SESSION = array();

        $game = is_null($hash) ? $this->_createGame() : $this->_getGameByHash($hash);

        if ($game === false) {
            return false;
        }

        $events        = $this->_getEvents($game['id'], array("shot", "join_game"));

        $player_number = $game['player_number'];
        $other_number  = $player_number == 1 ? 2 : 1;
        $player_prefix = "player" . $player_number;
        $other_prefix  = "player" . $other_number;
        $shots         = array_key_exists('shot', $events)      ? $events['shot']      : array();
        $game_joined   = array_key_exists('join_game', $events) ? $events['join_game'] : array();


        $_SESSION['game_id']         = $game['id'];
        $_SESSION['player_hash']     = $game[$player_prefix.'_hash'];
        $_SESSION['other_hash']      = $game[$other_prefix.'_hash'];
        $_SESSION['player_number']   = $player_number;
        $_SESSION['other_number']    = $other_number;
        $_SESSION['player_name']     = $game[$player_prefix.'_name'];
        $_SESSION['other_name']      = $game[$other_prefix.'_name'];
        $_SESSION['player_ships']    = $game[$player_prefix.'_ships'] != "" ? unserialize($game[$player_prefix.'_ships']) : array();
        $_SESSION['other_ships']     = $game[$other_prefix.'_ships']  != "" ? unserialize($game[$other_prefix.'_ships']) : array();
        $_SESSION['game_timestamp']  = $game['timestamp'];
        $_SESSION['last_event']      = $this->_getLastEventId($game['id'], $other_number);
        $_SESSION['player_shots']    = array_key_exists($player_number, $shots) ? $shots[$player_number] : array();
        $_SESSION['other_shots']     = array_key_exists($other_number, $shots)  ? $shots[$other_number]  : array();
        $_SESSION['player_joined']   = array_key_exists($player_number, $game_joined);
        $_SESSION['other_joined']    = array_key_exists($other_number, $game_joined);
        $_SESSION['timezone_offset'] = 0;

        // for now there's no sense to add join_game event for player 1
        if ($_SESSION['player_number'] == 2 && !$_SESSION['player_joined']) {
            $this->joinGame();
        }

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
    private function _getGameByHash($hash)
    {
        // what when 2 hashes found?
        $query = "SELECT *, CASE WHEN player1_hash = :hash THEN 1 ELSE 2 END AS player_number
                  FROM games
                  WHERE player1_hash = :hash OR player2_hash = :hash";
        $result = $this->_fQuery($query, array(':hash' => $hash));

        if ($result === false) {
            return false;
        }
        else if (count($result) == 0) {
            $this->_setError("Game (" . $hash . ") does not exist");
            return false;
        }


        return $result[0];
    }

    /**
     * Creates a new game
     *
     * @return array|false New game row from DB or false on error
     */
    private function _createGame()
    {
        $hash     = hash("md5", session_id() . microtime(true) . rand());
        $temphash = hash("md5", $hash . rand());

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
        $result = $this->_fQuery($query, array_values($game));

        if ($result === false) {
            return false;
        }

        $game['player_number'] = 1; // player who starts is always No. 1
        $game['id'] = $this->_DB->lastInsertId();

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
        $query = sprintf("UPDATE games SET player%d_name = ? WHERE id = ?", $_SESSION['player_number']);
        $result = $this->_fQuery($query, array($player_name, $_SESSION['game_id']));

        if ($result === false) {
            return false;
        }

        $this->_addEvent('name_update', $player_name);
        $_SESSION['player_name'] = $player_name;

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
        $result = $this->_fQuery($query, array($_SESSION['last_event'], $_SESSION['game_id'], $_SESSION['other_number']));

        if ($result === false) {
            return false;
        }

        $updates = array();
        foreach ($result as $value) {
            switch ($value['event_type']) {
                case "name_update":
                    $_SESSION['other_name'] = $value['event_value'];
                    self::$sessionUpdates['other_name'] = $_SESSION['other_name'];
                    break;

                case "start_game":
                    $_SESSION['other_ships'] = unserialize($value['event_value']);
                    self::$sessionUpdates['other_ships'] = $_SESSION['other_ships'];
                    break;

                case "shot":
                    $_SESSION['other_shots'][] = $value['event_value'];
                    self::$sessionUpdates['other_shots'] = $_SESSION['other_shots'];
                    break;
            }

            $_SESSION['last_event'] = max($_SESSION['last_event'], $value['id']);
            self::$sessionUpdates['last_event'] = $_SESSION['last_event'];

            if ($value['event_type'] == "chat") {
                $event_value = array(
                    'text' => $value['event_value'],
                    'time' => date("Y-m-d H:i:s", $value['timestamp'] + $_SESSION['timezone_offset'])
                );
            }
            else if ($value['event_type'] == 'start_game') {
                $event_value = true;
            }
            else {
                $event_value = $value['event_value'];
            }

            $updates[] = array('action' => $value['event_type'], 'value' => $event_value);
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
    private function _addEvent($event_type, $event_value = 1)
    {
        $query = "INSERT INTO events (game_id, player, event_type, event_value, timestamp) VALUES (?, ?, ?, ?, ?)";
        $result = $this->_fQuery($query, array($_SESSION['game_id'], $_SESSION['player_number'], $event_type, $event_value, utc_time()));

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Starts the game
     *
     * Updates the game with the ships provided
     *
     * @param array $ships Ships set by the player (Example: array("A1", "B4", "J10", ...))
     *
     * @return bool Whether the game started successfully
     */
    public function startGame($ships)
    {
        if (!self::checkShips($ships) || !empty($_SESSION['player_ships'])) {
            return false;
        }

        // check whether all coordinates are correct (e.g. A1, B4, J10)
        foreach ($ships as $coords) {
            if (self::_coordsInfo($coords) === false) {
                $this->_setError("Ship's coordinates are incorrect (" . $coords . ")");
                return false;
            }
        }

        $query = sprintf("UPDATE games SET player%d_ships = ? WHERE id = ?", $_SESSION['player_number']);
        $result = $this->_fQuery($query, array(serialize($ships), $_SESSION['game_id']));

        if ($result === false) {
            return false;
        }

        $this->_addEvent("start_game", serialize($ships));
        $_SESSION['player_ships'] = $ships;

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
        if (self::_coordsInfo($coords) === false) {
            $this->_setError("Shot's coordinates are incorrect (" . $coords . ")");
            return false;
        }

        $this->_addEvent("shot", $coords);
        $_SESSION['player_shots'][] = $coords;

        // If other ship at these coordinates (if hit)
        if (array_search($coords, $_SESSION['other_ships']) === false) {
            $result = "miss";
        }
        // If other ship is sunk after this hit
        else if (self::_checkSunk($coords)) {
            $result = "sunk";
        }
        else {
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
     * @param int $direction Direction which is checked for ship's masts
     *
     * @return bool Whether the ship is sunk after this shot or not
     */
    private static function _checkSunk($coords, $direction = null)
    {
        $coordsInfo = self::_coordsInfo($coords);
        if ($coordsInfo === false) {
            $this->_setError("Coordinates are incorrect (" . $coords . ")");
            return false;
        }

        $check_sunk = true;

        // neighbour coordinates, taking into consideration edge positions (A and J rows, 1 and 10 columns)
        $sunk_coords = array(
            $coordsInfo['position_y'] > 0 ? self::$_axisY[$coordsInfo['position_y'] - 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_y'] < 9 ? self::$_axisY[$coordsInfo['position_y'] + 1] . $coordsInfo['coord_x'] : "",
            $coordsInfo['position_x'] < 9 ? $coordsInfo['coord_y'] . self::$_axisX[$coordsInfo['position_x'] + 1] : "",
            $coordsInfo['position_x'] > 0 ? $coordsInfo['coord_y'] . self::$_axisX[$coordsInfo['position_x'] - 1] : ""
        );

        // try to find a mast which hasn't been hit
        foreach ($sunk_coords as $key => $value) {
            // if no coordinate on this side (end of the board) or direction is specified, but it's not the specified one
            if ($value === "" || ($direction !== null && $direction !== $key)) {
                continue;
            }

            $ship = array_search($value, $_SESSION['other_ships']);
            $shot = array_search($value, $_SESSION['player_shots']);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $check_sunk = self::_checkSunk($value, $key);
            }
            // if mast hasn't been hit, the the ship can't be sunk
            elseif ($ship !== false) {
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
     * @param int $game_id Id of the game
     *
     * @return array Shots for the game per player (Example: array(1 => array("A1", "B4", "J10"), 2 => array("C3", "F6", "I1"))
     */
    public function getShots($game_id)
    {
        $events = $this->_getEvents($game_id, 'shot');
        return array_key_exists('shot', $events) ? $events['shot'] : array();
    }

    /**
     * Gets chats for the game
     *
     * Returns all chats for a requested game
     *
     * @param int $game_id Id of the game
     *
     * @return array|false Chats for the game (Example: array(0 => array('name' => {player_name}, 'text' => "Hi!", 'time' => "2012-11-05 23:34"),  ...))
     */
    public function getChats($game_id)
    {
        $chats = array();

        $result = $this->_getEvents($game_id, "chat", true);
        if ($result === false) {
            return false;
        }

        // raw events result requested to build a custom array with chats' details
        foreach ($result as $value) {
            $chats[] = array(
                'name' => ($value['player'] == $_SESSION['player_number'] ? $_SESSION['player_name'] : $_SESSION['other_name']),
                'text' => $value['event_value'],
                'time' => date("Y-m-d H:i:s", $value['timestamp'] + $_SESSION['timezone_offset'])
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
     * @param int $game_id Id of the game
     * @param string|array $event_type Types of events to be returned (all if not/null provided)
     * @param bool $raw Whether to return a query result or group the result by even_type and player_number
     *
     * @return array|false Events for the game (Example: array('chat' => array(1 => array(0 => "Hi!", 2 => ...), 'shot' => array(1 => array(0 => "A1", ...), ...)
     */
    private function _getEvents($game_id, $event_type, $raw = false)
    {
        $query = "SELECT * FROM events WHERE game_id = ? AND event_type IN (:event_types)";
        $result = $this->_fQuery($query, array($game_id, ':event_types' => $event_type));

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
     * @param int $game_id Id of the game
     * @param int $player Player number (1|2)
     *
     * @return int|false Id of the last event made by the player
     */
    private function _getLastEventId($game_id, $player)
    {
        $query = "SELECT MAX(id) AS id FROM events WHERE game_id = ? AND player = ? GROUP BY game_id";
        $result = $this->_fQuery($query, array($game_id, $player));

        if ($result === false) {
            return false;
        }

        return empty($result) ? 0 : (int)$result[0]['id'];
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
    public static function getBattle()
    {
        $prefixes = array(array("player", "other"), array("other", "player"));

        $battle['player_ships'] = $_SESSION['player_ships'];

        foreach ($prefixes as $prefix) {
            foreach ($_SESSION[ $prefix[0].'_shots' ] as $value) {
                if (array_search($value, $_SESSION[ $prefix[1].'_ships' ]) === false) {
                    $shot = "miss";
                }
                else if (self::_checkSunk($value)) {
                    $shot = "sunk";
                }
                else {
                    $shot = "hit";
                }

                $battle[ $prefix[0].'_shots' ][$value] = $shot;
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
        return $this->_addEvent("chat", $text);
    }

    /**
     * Marks that a player joined current game
     *
     * Adds new 'joing_game' event
     *
     * @return bool Whether 'join_game' event was inserted successfully
     */
    public function joinGame()
    {
        return $this->_addEvent("join_game");
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
    private static function _toIndex($coords)
    {
        $coordsInfo = self::_coordsInfo($coords);
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
    private static function _coordsInfo($coords)
    {
        $coord_y    = $coords[0];
        $coord_x    = substr($coords, 1);

        $position_y = array_search($coord_y, self::$_axisY);
        $position_x = array_search($coord_x, self::$_axisX);

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
     * @param array $ships Ships set by the player (Example: array("A1", "B4", "J10", ...))
     *
     * @return string Two indexes (Y and X) concatenated
     */
    public static function checkShips($ships)
    {
        if (!is_array($ships)) {
            return false;
        }

        // converts all coordinates to indexes and sorts them for more efficient validation
        $ships_array = array_map("self::_toIndex", $ships);
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
        if ( !empty($diff) ) {
            return false;
        }

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
                    $text = self::$_axisY[($j - 1)];
                }
                else if ($j == 0 && $i > 0) {
                    $text = self::$_axisX[($i - 1)];
                }
                else {
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

?>
