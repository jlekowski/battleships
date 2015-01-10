<?php

namespace Battleships\Game;

/**
 * Battleships\Game\Formatter class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class Formatter
{
    /**
     * @var \Battleships\Game\Manager
     */
    protected $oManager;

    /**
     * @param \Battleships\Game\Manager $oManager
     */
    public function __construct(Manager $oManager)
    {
        $this->oManager = $oManager;
    }

    /**
     * Get formatted data for a game
     *
     * @param bool $isNew
     * @return \stdClass
     */
    public function getForGame($isNew = false)
    {
        $formatted = new \stdClass();
        $oData = $this->oManager->oData;

        $formatted->playerHash = $oData->getPlayerHash();
        $formatted->otherHash = $isNew || !$oData->getOtherJoined() ? $oData->getOtherHash() : "";
        $formatted->playerName = $oData->getPlayerName();
        $formatted->otherName = $oData->getOtherName();
        $formatted->playerNumber = $oData->getPlayerNumber();
        $formatted->otherNumber = $oData->getOtherNumber();
        $formatted->playerStarted = $oData->getPlayerStarted();
        $formatted->lastIdEvents = $oData->getLastIdEvents();
        $formatted->whoseTurn = $oData->getWhoseTurn();
        if (!$isNew) {
            $formatted->playerShips = $oData->getPlayerShips();
            $formatted->otherJoined = $oData->getOtherJoined();
            $formatted->otherStarted = $oData->getOtherStarted();
            $formatted->battle = $this->oManager->getBattle();
            $formatted->chats = $this->oManager->getChats();
        }

        return $formatted;
    }
}
