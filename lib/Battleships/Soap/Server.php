<?php
namespace Battleships\Soap;

use Battleships\Game\Manager;
use Battleships\Misc;

/**
 * Battleships\Soap\Server class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.5
 *
 */
class Server
{
    /**
     * @var Battleships\Game\Manager
     */
    private $oManager;

    /**
     * @param \Battleships\Game\Manager $oManager
     */
    public function __construct(Manager $oManager)
    {
        $this->oManager = $oManager;
    }

    public function getGame($hash = "", $timezoneOffset = 0)
    {
        $this->oManager->initGame($hash);
        $oData = $this->oManager->oData;
        $oData->battle = $this->oManager->getBattle();
        $oData->chats = $this->oManager->getChats();
        // so that no one could see other player's ships
        $oData->setOtherShips("");
        // if other player joined, don't show his new hash
        if ($oData->getOtherJoined()) {
            $oData->setOtherHash("");
        }

        if (abs($timezoneOffset) <= 14) {
            $oData->setTimezoneOffset($timezoneOffset * 60 * 60);
        }

        return $oData;
    }

    public function updateName($hash, $playerName)
    {
        $this->oManager->initGame($hash);
        return $this->oManager->updateName($playerName);
    }

    public function startGame($hash, $ships)
    {
        $this->oManager->initGame($hash);
        return $this->oManager->startGame(strtoupper($ships));
    }

    public function addShot($hash, $coords)
    {
        $this->oManager->initGame($hash);
        return $this->oManager->addShot(strtoupper($coords));
    }

    public function getUpdates($hash, $lastIdEvents)
    {
        $this->oManager->initGame($hash);
        $this->oManager->oData->setLastIdEvents($lastIdEvents);

        $i = 1;
        while (true) {
            $updates = $this->oManager->getUpdates();

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
        $this->oManager->initGame($hash);
        $result = $this->oManager->addChat($text)
            ? Misc::getUtcTime()->modify($this->oManager->oData->getTimezoneOffset() . "hour")->format("Y-m-d H:i:s")
            : "";

        return $result;
    }
}
