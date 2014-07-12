<?php

namespace Battleships\Soap;

use Battleships\Game\Data;
use Battleships\Misc;

/**
 * Battleships\Soap\Client class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.5
 *
 */
class Client
{
    /**
     * @var \SoapClient
     */
    private $soapClient;

    /**
     * Initiate SOAP Client
     */
    public function __construct()
    {
        try {
            $this->soapClient = new \SoapClient(WSDL_URL, array('cache_wsdl' => WSDL_CACHE_NONE, 'trace' => true));
        } catch (\SoapFault $e) {
            Misc::log($e);
        }
    }

    /**
     *
     * @param string $hash
     * @return Battleships\Game\Data
     */
    public function getGame($hash)
    {
        try {
            $game = $this->soapClient->getGame($hash);
        } catch (\SoapFault $e) {
            Misc::log($e);
        }

        return new Data($game);
    }

    public function updateName(Data $oData, $playerName)
    {
        try {
            $result = $this->soapClient->updateName($oData->getPlayerHash(), $playerName);
            if ($result) {
                $oData->setPlayerName($playerName);
            }
        } catch (\SoapFault $e) {
            Misc::log($e);
        }
    }

    public function startGame(Data $oData, $ships)
    {
        try {
            $result = $this->soapClient->startGame($oData->getPlayerHash(), $ships);
            if ($result) {
                $oData->setPlayerShips($ships);
                $shipsArray = explode(",", $ships);
                foreach ($shipsArray as $value) {
                    $oData->battle['playerGround'][$value] = "ship";
                }
            }
        } catch (\SoapFault $e) {
            Misc::log($e);
        }

        return $result;
    }

    public function addShot(Data $oData, $coords)
    {
        try {
            $result = $this->soapClient->addShot($oData->getPlayerHash(), $coords);
            if ($result) {
                $oData->appendPlayerShots($coords);
                $oData->battle['otherGround'][$coords] = $result;
                $whoseTurn = $result == "miss" ? $oData->getOtherNumber() : $oData->getPlayerNumber();
                $oData->setWhoseTurn($whoseTurn);
            }
        } catch (\SoapFault $e) {
            Misc::log($e);
        }

        return $result;
    }

    public function getUpdates(Data $oData)
    {
        try {
            $result = $this->soapClient->getUpdates($oData->getPlayerHash(), $oData->getLastIdEvents());
        } catch (\SoapFault $e) {
            Misc::log($e);
        }

        foreach ($result as $action => $updates) {
            foreach ($updates as $update) {
                $this->applyUpdate($oData, $update, $action);
            }
        }

        return $result;
    }

    private function applyUpdate(Data $oData, $update, $action)
    {
        switch ($action) {
            case "name_update":
                $oData->setOtherName($update);
                break;

            case "start_game":
                $oData->setOtherStarted(true);
                break;

            case "join_game":
                $oData->setOtherJoined(true);
                break;

            case "shot":
                $oData->appendOtherShots($update);

                if (in_array($update, $oData->getPlayerShips())) {
                    $shotResult = "hit";
                    $whoseTurn = $oData->getOtherNumber();
                } else {
                    $shotResult = "miss";
                    $whoseTurn = $oData->getPlayerNumber();
                }

                $oData->battle['playerGround'][ $update ] = $shotResult;
                $oData->setWhoseTurn($whoseTurn);
                break;

            case "chat":
                break;

            case "lastIdEvents":
                $oData->setLastIdEvents($update);
                break;
        }
    }
}
