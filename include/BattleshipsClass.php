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
 * @todo       _prefix for privates
 * @todo       code formatting change
 *
 *
 */

class Battleships {

    # Variables
    private $db_obj;

    private $error;

    private static $axis_y = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
    private static $axis_x = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

    public static $sessionUpdates = array(); // gathers updates when session is stopped


    #Getters
    public function getError()    { return $this->error; }


    # Methods
    public function __construct() {
        $file_exists = file_exists(SQLITE_PATH . SQLITE_FILE);

        try {
            $this->db_obj = new PDO("sqlite:" . SQLITE_PATH . SQLITE_FILE);
        }
        catch( PDOException $e ) {
            $this->error = $e->getMessage();
        }

        if( $file_exists === false && is_null($this->error) ) {
            $this->createTables();
        }
    }


    private function setDbError() {
        $this->error = implode(' | ', $this->db_obj->errorInfo());
    }


    protected function query($query) {
        $result = $this->db_obj->query( $query );

        if( $result === false ) {
            $this->setDbError();
            return false;
        }

        return $result->fetchall(PDO::FETCH_ASSOC);
    }


    private function createTables() {
        $query = array();

        # creating games table
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

        # creating events table
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

        foreach( $query as $value ) {
            $this->query($value);
        }
    }


    private function escapeString($text, $esc_quotes = true) {
        # don't escape serialized string (" -> &quot;)
        $flags = $esc_quotes ? ENT_COMPAT : ENT_NOQUOTES;

        return $this->db_obj->quote( htmlentities(strip_tags($text), $flags) );
    }


    public function gameInitiate($hash = null) {
        $_SESSION = array();

        $game = is_null($hash) ? $this->gameCreate() : $this->getGameByHash($hash);

        if( $game === false ) {
            return false;
        }

        $events        = $this->getEvents($game['id'], array('shot', 'join_game'));

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
        $_SESSION['last_event']      = $this->getLastEvent($game['id'], $other_number);
        $_SESSION['player_shots']    = array_key_exists($player_number, $shots) ? $shots[$player_number] : array();
        $_SESSION['other_shots']     = array_key_exists($other_number, $shots)  ? $shots[$other_number]  : array();
        $_SESSION['player_joined']   = array_key_exists($player_number, $game_joined);
        $_SESSION['other_joined']    = array_key_exists($other_number, $game_joined);
        $_SESSION['timezone_offset'] = 0;

        // for now there's no sense to add join_game event for player 1
        if( $_SESSION['player_number'] == 2 && !$_SESSION['player_joined'] ) {
            $this->gameJoin();
            /* @todo: generate hash, edit it in DB and redirect to the new */
        }

        return true;
    }


    private function getGameByHash($hash) {
        $esc_hash = $this->escapeString($hash);

        // what when 2 hashes found?
        $query  = "
            SELECT
                *,
                CASE WHEN player1_hash = " . $esc_hash . " THEN 1 ELSE 2 END AS player_number
            FROM
                games
            WHERE
                   player1_hash = " . $esc_hash . "
                OR player2_hash = " . $esc_hash . "
        ";
        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }
        else if( count($result) == 0 ) {
            $this->error = "Game (" . $hash . ") does not exist";
            return false;
        }


        return $result[0];
    }


    private function gameCreate() {
        $hash     = hash( 'md5', session_id() . microtime(true) . rand() );
        $temphash = hash( 'md5', $hash . rand() );

        $game     = array(
            'player1_hash'  => $hash,
            'player1_name'  => "Player 1",
            'player1_ships' => "",
            'player2_hash'  => $temphash,
            'player2_name'  => "Player 2",
            'player2_ships' => "",
            'timestamp'     => utc_time(),
            'player_number' => 1
        );

        // uglyyyy
        $query    = "
            INSERT INTO
                games (
                    player1_hash,
                    player1_name,
                    player1_ships,
                    player2_hash,
                    player2_name,
                    player2_ships,
                    timestamp
                )
            VALUES (
                '" . $game['player1_hash']  . "',
                '" . $game['player1_name']  . "',
                '" . $game['player1_ships'] . "',
                '" . $game['player2_hash']  . "',
                '" . $game['player2_name']  . "',
                '" . $game['player2_ships'] . "',
                 " . $game['timestamp']     . "
            )
        ";

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }

        $game['id'] = $this->db_obj->lastInsertId();

        return $game;
    }


    public function updateName($player_name) {
        $esc_player_name = $this->escapeString($player_name);

        $query = "
            UPDATE
                games
            SET
                player" . $_SESSION['player_number'] . "_name = " . $esc_player_name . "
            WHERE
                id = " . $_SESSION['game_id']
        ;

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }

        // removing first and last '
        $_SESSION['player_name'] = substr($esc_player_name, 1, strlen($esc_player_name) - 2);
        $this->addEvent('name_update', $_SESSION['player_name']);

        return true;
    }


    public function getUpdates() {
        $updates = array();

        $query = "
            SELECT
                *
            FROM
                events
            WHERE
                    id      > " . $_SESSION['last_event'] . "
                AND game_id = " . $_SESSION['game_id'] . "
                AND player  = " . $_SESSION['other_number'] . "
        ";

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }


        foreach( $result as $value ) {
            switch( $value['event_type'] ) {
                case 'name_update':
                    $_SESSION['other_name'] = $value['event_value'];
                    self::$sessionUpdates['other_name'] = $_SESSION['other_name'];
                    break;

                case 'start_game':
                    $_SESSION['other_ships'] = unserialize($value['event_value']);
                    self::$sessionUpdates['other_ships'] = $_SESSION['other_ships'];
                    break;

                case 'shot':
                    $_SESSION['other_shots'][] = $value['event_value'];
                    self::$sessionUpdates['other_shots'] = $_SESSION['other_shots'];
                    break;
            }

            $_SESSION['last_event'] = max($_SESSION['last_event'], $value['id']);
            self::$sessionUpdates['last_event'] = $_SESSION['last_event'];

            if( $value['event_type'] == 'chat' ) {
                $event_value = array(
                    'text' => $value['event_value'],
                    'time' => date("Y-m-d H:i:s", $value['timestamp'] + $_SESSION['timezone_offset'])
                );
            }
            else if( $value['event_type'] == 'start_game' ) {
                $event_value = true;
            }
            else {
                $event_value = $value['event_value'];
            }

            $updates[] = array('action' => $value['event_type'], 'value' => $event_value);
        }

        return $updates;
    }


    private function addEvent($event_type, $event_value = 1) {
        $esc_event_type  = $this->escapeString($event_type, false);
        $esc_event_value = $this->escapeString($event_value, false);

        $query = "
            INSERT INTO
                events (
                    game_id,
                    player,
                    event_type,
                    event_value,
                    timestamp
                )
            VALUES (
                " . $_SESSION['game_id']       . ",
                " . $_SESSION['player_number'] . ",
                " . $esc_event_type            . ",
                " . $esc_event_value           . ",
                " . utc_time()                 . "
            )
        ";

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }

        return true;
    }


    public function startGame($ships) {
        if( !is_array($ships) || !empty($_SESSION['player_ships']) ) {
            return false;
        }


        foreach( $ships as $value ) {
            $position_y = array_search($value[0], self::$axis_y);
            $position_x = array_search($value[1], self::$axis_x);

            if( $position_y === false || $position_x === false ) {
                return false;
            }
        }


        $query = "
            UPDATE
                games
            SET
                player" . $_SESSION['player_number'] . "_ships = '" . serialize($ships) . "'
            WHERE
                id = " . $_SESSION['game_id']
        ;

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }

        $this->addEvent('start_game', serialize($ships));

        $_SESSION['player_ships'] = $ships;

        return true;
    }


    public function shot($coords) {
        $coord_y = $coords[0];
        $coord_x = substr($coords, 1);

        $position_y = array_search($coord_y, self::$axis_y);
        $position_x = array_search($coord_x, self::$axis_x);

        if( $position_y === false || $position_x === false ) {
            return false;
        }

        $this->addEvent('shot', $coords);
        $_SESSION['player_shots'][] = $coords;

        $shot = array_search($coords, $_SESSION['other_ships']);

        $result = $shot === false ? "miss" : (self::check_sunk($coords) ? "sunk" : "hit");

        return $result;
    }


    private static function check_sunk($coords, $tendency = null) {
        $coord_y    = $coords[0];
        $coord_x    = substr($coords, 1);
        $check_sunk = true;

        $position_y = array_search($coord_y, self::$axis_y);
        $position_x = array_search($coord_x, self::$axis_x);

        $sunk_coords = array(
            $position_y > 0 ? self::$axis_y[ $position_y - 1 ] . $coord_x : '',
            $position_y < 9 ? self::$axis_y[ $position_y + 1 ] . $coord_x : '',
            $position_x < 9 ? $coord_y . self::$axis_x[ $position_x + 1 ] : '',
            $position_x > 0 ? $coord_y . self::$axis_x[ $position_x - 1 ] : ''
        );

        foreach( $sunk_coords as $key => $value ) {
            if( $value === '' || ($tendency !== null && $tendency !== $key) ) {
                continue;
            }

            $ship = array_search($value, $_SESSION['other_ships']);
            $shot = array_search($value, $_SESSION['player_shots']);

            if( $ship !== false && $shot !== false ) {
                $check_sunk = self::check_sunk( $value, $key );
            }
            elseif( $ship !== false ) {
                $check_sunk = false;
            }


            if( $check_sunk === false ) {
                break;
            }
        }

        return $check_sunk;
    }


    public function getShots($game_id) {
        $events = $this->getEvents($game_id, 'shot');
        return array_key_exists('shot', $events) ? $events['shot'] : array();
    }


    public function getChats($game_id) {
        $chats = array();

        $result = $this->getEvents($game_id, 'chat', true);
        if( $result === false ) {
            return false;
        }

        foreach( $result as $value ) {
            $chats[] = array(
                'name' => ($value['player'] == $_SESSION['player_number'] ? $_SESSION['player_name'] : $_SESSION['other_name']),
                'text' => $value['event_value'],
                'time' => date("Y-m-d H:i:s", $value['timestamp'] + $_SESSION['timezone_offset'])
            );
        }

        return $chats;
    }


    private function getEvents($game_id, $event_type = null, $raw = false) {
        $events = array();

        $query = "
            SELECT
                *
            FROM
                events
            WHERE
                game_id = " . $game_id . "
                " . (is_null($event_type) ? "" : "AND event_type IN('".implode("','", (array)$event_type)."')") . "
        ";

        $result = $this->query($query);

        if( $raw || $result === false ) {
            return $result;
        }


        foreach( $result as $value ) {
            $events[ $value['event_type'] ][ $value['player'] ][] = $value['event_value'];
        }

        return $events;
    }


    private function getLastEvent($game_id, $other_number) {
        $query = "
            SELECT
                MAX(id) AS id
            FROM
                events
            WHERE
                    game_id = " . $game_id . "
                AND player  = " . $other_number . "
            GROUP BY
                game_id
        ";

        $result = $this->query($query);

        if( $result === false ) {
            return false;
        }

        return empty($result) ? 0 : (int)$result[0]['id'];
    }


    public static function getBattle() {
        $prefixes = array( array("player", "other"), array("other", "player") );

        $battle['player_ships'] = $_SESSION['player_ships'];

        foreach( $prefixes as $prefix ) {
            foreach( $_SESSION[ $prefix[0].'_shots' ] as $value ) {
                if( array_search($value, $_SESSION[ $prefix[1].'_ships' ]) === false ) {
                    $shot = "miss";
                }
                else if( self::check_sunk($value) ) {
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


    public function chat($text) {
        return $this->addEvent('chat', $text);
    }


    public function gameJoin() {
        return $this->addEvent('join_game');
    }


    private static function _toIndex($coords) {
        $coord_y    = $coords[0];
        $coord_x    = substr($coords, 1);

        $position_y = array_search($coord_y, self::$axis_y);
        $position_x = array_search($coord_x, self::$axis_x);

        return $position_y.$position_x;
    }


    public static function ships_check($ships) {
        $ships_array = array_map("self::_toIndex", $ships);
        sort($ships_array);

        $ships_length = 20;
        $ships_types  = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        $direction_multipliers = array(1, 10);

        if( count($ships_array) != $ships_length ) {
            return false;
        }


        // check if no edge connection
        foreach( $ships_array as $key => $value) {
            if( $value[0] == 9 ) {
                continue;
            }

            $upper_right_corner = ($value[1] > 0) && (in_array($value + 9, $ships_array));
            $lower_right_corner = ($value[1] < 9) && (in_array($value + 11, $ships_array));

            if( $upper_right_corner || $lower_right_corner ) {
                return false;
            }
        }

        $masts = array();

        // check if there are the right types of ships
        foreach( $ships_array as $key => $value) {
            // we ignore masts which have already been marked as a part of a ship
            if( array_key_exists($value, $masts) ) {
                continue;
            }

            foreach( $direction_multipliers as $k => $v ) {
                $border_index    = $k == 1 ? 0 : 1;
                $border_distance = $value[$border_index];

                $type = 1;
                // battleground border
                while( $border_distance + $type <= 9 ) {
                    $index = sprintf("%02s", $value + ($type * $v));

                    // no more masts
                    if( !in_array($index, $ships_array) ) {
                        break;
                    }

                    $masts[$index] = true;

                    // ship is too long
                    if( ++$type > 4 ) {
                        return false;
                    }
                }

                // if not last direction check and only one (otherwise in both direction at least 1 mast would be found)
                if( ($type == 1) && ($k + 1 != count($direction_multipliers)) ) {
                    continue;
                }

                break; // either $k > 1 (so ship found) or last loop
            }

            $ships_types[$type]++;
        }

        // strange way to check if ships_types == {1:4, 2:3, 3:2, 4:1}
        foreach( $ships_types as $key => $value) {
            if( $key + $value != 5 ) {
                return false;
            }
        }

        return true;
    }


    public static function board_create() {
        $board  = "<div class='board'>\n";

        for( $i=0; $i < 11; $i++ ) {
            $board .= "  <div>\n";

            for( $j=0; $j < 11; $j++ ) {
                if( $i == 0 && $j > 0 ) {
                    $text = self::$axis_y[($j - 1)];
                }
                else if( $j == 0 && $i > 0 ) {
                    $text = self::$axis_x[($i - 1)];
                }
                else {
                    $text = "";
                }

                $board .= "    <div>$text</div>\n";
            }

            $board .= "  </div>\n";
        }

        $board .= "</div>";


        return $board;
    }

}

?>
