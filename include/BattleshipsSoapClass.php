<?php

/**
 * BattleshipsSoap class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.3
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class BattleshipsSoap extends Battleships
{
    public function getGame($hash = "", $timezoneOffset = 0)
    {
        parent::initGame($hash);
        $this->oBattleshipsGame->battle = $this->getBattle();
        $this->oBattleshipsGame->chats = $this->getChats();
        // so that no one could see other player's ships
        $this->oBattleshipsGame->setOtherShips("");
        // if other player joined, don't show his new hash
        if ($this->oBattleshipsGame->getOtherJoined()) {
            $this->oBattleshipsGame->setOtherHash("");
        }

        if (abs($timezoneOffset) <= 14) {
            $this->oBattleshipsGame->setTimezoneOffset($timezoneOffset * 60 * 60);
        }

        return $this->oBattleshipsGame;
    }

    public function updateName($hash, $player_name)
    {
        parent::initGame($hash);
        return parent::updateName($player_name);
    }

    public function startGame($hash, $ships)
    {
        parent::initGame($hash);
        return parent::startGame(strtoupper($ships));
    }

    public function addShot($hash, $coords)
    {
        parent::initGame($hash);
        return parent::addShot(strtoupper($coords));
    }

    public function getUpdates($hash, $lastIdEvents)
    {
        parent::initGame($hash);
        $this->oBattleshipsGame->setLastIdEvents($lastIdEvents);

        $i = 1;
        while (true) {
            $updates = parent::getUpdates();

            // if updates, or no updates and limit is reached
            if (count((array)$updates) > 0 || (++$i > CHECK_UPDATES_COUNT)) {
                break;
            } else {
                sleep(CHECK_UPDATES_INTERVAL);
            }
        }

        return $updates;
    }

    public function addChat($hash, $text)
    {
        parent::initGame($hash);
        $result = parent::addChat($text)
            ? date("Y-m-d H:i:s", utc_time() + $this->oBattleshipsGame->getTimezoneOffset())
            : "";

        return $result;
    }
}
