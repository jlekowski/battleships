<?php

namespace Battleships\Game;

/**
 * Battleships\Game\Data class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class Data
{
    public $battle;
    public $chats;
    private $idGames = 0;
    private $gameTimestamp;
    private $playerNumber;
    private $otherNumber;
    private $playerHash = "";
    private $otherHash = "";
    private $playerName = "";
    private $otherName = "";
    private $playerShips = "";
    private $otherShips = "";
    private $playerShots = "";
    private $otherShots = "";
    private $playerJoined = false;
    private $otherJoined = false;
    private $playerStarted = false;
    private $otherStarted = false;
    private $lastIdEvents = 0;
    private $timezoneOffset = 0;
    private $whoseTurn;

    public function __construct(\stdClass $game = null)
    {
        if (!$game) {
            return;
        }

        foreach ($this as $property => &$value) {
            if (property_exists($game, $property)) {
                $gameValue = $game->$property;
                if (in_array($property, array("playerShips", "otherShips")) && is_array($gameValue)) {
                    $gameValue = implode(",", $gameValue);
                }
                $value = $gameValue;
            }
        }
    }

    public function getIdGames()
    {
        return $this->idGames;
    }

    public function setIdGames($idGames)
    {
        $this->idGames = $idGames;
    }

    public function getGameTimestamp()
    {
        return $this->gameTimestamp;
    }

    public function setGameTimestamp($gameTimestamp)
    {
        $this->gameTimestamp = $gameTimestamp;
    }

    public function getPlayerNumber()
    {
        return $this->playerNumber;
    }

    public function setPlayerNumber($playerNumber)
    {
        $this->playerNumber = (int)$playerNumber;
    }

    public function getOtherNumber()
    {
        return $this->otherNumber;
    }

    public function setOtherNumber($otherNumber)
    {
        $this->otherNumber = (int)$otherNumber;
    }

    public function getPlayerHash()
    {
        return $this->playerHash;
    }

    public function setPlayerHash($playerHash)
    {
        $this->playerHash = $playerHash;
    }

    public function getOtherHash()
    {
        return $this->otherHash;
    }

    public function setOtherHash($otherHash)
    {
        $this->otherHash = $otherHash;
    }

    public function getPlayerName()
    {
        return $this->playerName;
    }

    public function setPlayerName($playerName)
    {
        $this->playerName = $playerName;
    }

    public function getOtherName()
    {
        return $this->otherName;
    }

    public function setOtherName($otherName)
    {
        $this->otherName = $otherName;
    }

    /**
     * Get player's ships
     * @return array
     */
    public function getPlayerShips()
    {
        return $this->playerShips ? explode(",", $this->playerShips) : array();
    }

    public function setPlayerShips($playerShips)
    {
        $this->playerShips = is_array($playerShips) ? implode(",", $playerShips) : $playerShips;
    }

    /**
     * Get opponent's ships
     * @return array
     */
    public function getOtherShips()
    {
        return $this->otherShips ? explode(",", $this->otherShips) : array();
    }

    public function setOtherShips($otherShips)
    {
        $this->otherShips = is_array($otherShips) ? implode(",", $otherShips) : $otherShips;
    }

    /**
     * Get ships for a player (owner)
     * @param string $owner (player|other)
     * @return array Ships coordinates
     * @throws \InvalidArgumentException
     */
    public function getShips($owner)
    {
        if (!in_array($owner, array("player", "other"))) {
            throw new \InvalidArgumentException("Invalid ships' owner: " . $owner);
        }

        return $owner == "player" ? $this->getPlayerShips() : $this->getOtherShips();
    }

    public function getPlayerShots()
    {
        return $this->playerShots ? explode(",", $this->playerShots) : array();
    }

    public function setPlayerShots($playerShots)
    {
        $this->playerShots = is_array($playerShots) ? implode(",", $playerShots) : $playerShots;
    }

    public function appendPlayerShots($playerShots)
    {
        $this->playerShots .= ($this->playerShots ? "," : "") . $playerShots;
    }

    public function getOtherShots()
    {
        return $this->otherShots ? explode(",", $this->otherShots) : array();
    }

    public function setOtherShots($otherShots)
    {
        $this->otherShots = is_array($otherShots) ? implode(",", $otherShots) : $otherShots;
    }

    public function appendOtherShots($otherShots)
    {
        $this->otherShots .= ($this->otherShots ? "," : "") . $otherShots;
    }

    public function getPlayerJoined()
    {
        return $this->playerJoined;
    }

    public function setPlayerJoined($playerJoined)
    {
        $this->playerJoined = (bool)$playerJoined;
    }

    public function getOtherJoined()
    {
        return $this->otherJoined;
    }

    public function setOtherJoined($otherJoined)
    {
        $this->otherJoined = (bool)$otherJoined;
    }

    public function getPlayer()
    {
        return $this->playerStarted;
    }

    public function setPlayerStarted($playerStarted)
    {
        $this->playerStarted = (bool)$playerStarted;
    }

    public function getOtherStarted()
    {
        return $this->otherStarted;
    }

    public function setOtherStarted($otherStarted)
    {
        $this->otherStarted = (bool)$otherStarted;
    }

    public function getLastIdEvents()
    {
        return $this->lastIdEvents;
    }

    public function setLastIdEvents($lastIdEvents)
    {
        $this->lastIdEvents = $lastIdEvents;
    }

    public function getTimezoneOffset()
    {
        return $this->timezoneOffset;
    }

    public function setTimezoneOffset($timezoneOffset)
    {
        $this->timezoneOffset = $timezoneOffset;
    }

    public function getWhoseTurn()
    {
        return $this->whoseTurn;
    }

    public function isMyTurn()
    {
        return $this->getWhoseTurn() == $this->getPlayerNumber();
    }

    public function setWhoseTurn($whoseTurn)
    {
        $this->whoseTurn = (int)$whoseTurn;
    }
}
