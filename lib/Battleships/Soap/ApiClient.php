<?php

namespace Battleships\Soap;

use Battleships\ApiClientInterface;
use Battleships\Game\Data;
use Battleships\Misc;

/**
 * Battleships\Soap\ApiClient class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.5
 *
 */
class ApiClient implements ApiClientInterface
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
     * Create game
     * @param string $name
     * @return \Battleships\Game\Data
     */
    public function createGame($name)
    {
        $game = $this->soapClient->getGame();

        return new Data($game);
    }

    /**
     *
     * @param string $hash
     * @return \Battleships\Game\Data
     */
    public function getGame($hash)
    {
        $game = $this->soapClient->getGame($hash);

        return new Data($game);
    }

    public function updateName(Data $oData, $playerName)
    {
        $this->soapClient->updateName($oData->getPlayerHash(), $playerName);
        $oData->setPlayerName($playerName);
    }

    /**
     * Add ships to start game
     * @param \Battleships\Game\Data $oData
     * @param array $ships
     */
    public function addShips(Data $oData, array $ships)
    {
        $result = $this->soapClient->startGame($oData->getPlayerHash(), implode(",", $ships));
        $oData->setPlayerShips($ships);
        foreach ($ships as $value) {
            $oData->battle->playerGround->{$value} = "ship";
        }

        return $result;
    }

    public function addShot(Data $oData, $coords)
    {
        $result = $this->soapClient->addShot($oData->getPlayerHash(), $coords);
        $oData->appendPlayerShots($coords);
        $oData->battle->otherGround->{$coords} = $result;
        $whoseTurn = $result == "miss" ? $oData->getOtherNumber() : $oData->getPlayerNumber();
        $oData->setWhoseTurn($whoseTurn);

        return $result;
    }

    /**
     * Add chat
     * @param \Battleships\Game\Data $oData
     * @param string $text
     */
    public function addChat(Data $oData, $text)
    {
        // TODO: Implement addChat() method.
    }

    /**
     * Get updates
     * @param \Battleships\Game\Data $oData
     * @return array
     */
    public function getUpdates(Data $oData)
    {
        $result = $this->soapClient->getUpdates($oData->getPlayerHash(), $oData->getLastIdEvents());

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

                $oData->battle->playerGround->{$update} = $shotResult;
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
