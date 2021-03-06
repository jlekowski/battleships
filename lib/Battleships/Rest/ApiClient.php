<?php

namespace Battleships\Rest;

use Battleships\ApiClientInterface;
use Battleships\Http\HttpClient;
use Battleships\Game\Data;
use Battleships\Exception\ApiClientException;
use Battleships\Misc;

/**
 * Battleships\Rest\ApiClient class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class ApiClient implements ApiClientInterface
{
    /**
     * @var \Battleships\Http\HttpClient
     */
    protected $oHttpClient;

    /**
     * @param \Battleships\Http\HttpClient $oHttpClient
     */
    public function __construct(HttpClient $oHttpClient)
    {
        $this->oHttpClient = $oHttpClient;
    }

    /**
     * Create game
     * @param string $name
     * @return \Battleships\Game\Data
     */
    public function createGame($name)
    {
        $gameData = new \stdClass();
        $gameData->name = $name;
        $game = $this->call("/games/", "POST", $gameData);

        return new Data($game);
    }

    /**
     * Get game data
     * @param string $hash
     * @return \Battleships\Game\Data
     */
    public function getGame($hash)
    {
        $game = $this->call("/games/" . $hash, "GET");

        return new Data($game);
    }

    public function updateName(Data $oData, $name)
    {
        $nameData = new \stdClass();
        $nameData->name = $name;
        $this->call("/games/" . $oData->getPlayerHash(), "PUT", $nameData);
        $oData->setPlayerName($name);
    }

    public function addShips(Data $oData, array $ships)
    {
        $shipsData = new \stdClass();
        $shipsData->ships = $ships;
        $this->call("/games/" . $oData->getPlayerHash() . "/ships", "POST", $shipsData);
        $oData->setPlayerShips($ships);
        foreach ($shipsData->ships as $ship) {
            $oData->battle->playerGround->{$ship} = "ship";
        }
    }

    public function addShot(Data $oData, $shot)
    {
        $shotData = new \stdClass();
        $shotData->shot = $shot;
        $result = $this->call("/games/" . $oData->getPlayerHash() . "/shots", "POST", $shotData);
        $shotResult = $result->shotResult;

        $oData->appendPlayerShots($shot);
        $oData->battle->otherGround->{$shot} = $shotResult;
        $whoseTurn = $shotResult == "miss" ? $oData->getOtherNumber() : $oData->getPlayerNumber();
        $oData->setWhoseTurn($whoseTurn);

        return $shotResult;
    }

    public function addChat(Data $oData, $text)
    {
        $chatData = new \stdClass();
        $chatData->text = $text;
        $result = $this->call("/games/" . $oData->getPlayerHash() . "/chats", "POST", $chatData);

        $chatData->name = $oData->getPlayerName();
        $chatData->time = $result->timestamp;
        $oData->chats[] = $chatData;
    }

    /**
     * Get updates
     * @param \Battleships\Game\Data $oData
     * @return array
     */
    public function getUpdates(Data $oData)
    {
        $result = $this->call("/games/" . $oData->getPlayerHash() . "/updates/" . $oData->getLastIdEvents(), "GET");
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

    /**
     *
     * @param string $request
     * @param string $method
     * @param \stdClass $data
     * @return mixed REST API response
     * @throws \Battleships\Exception\HttpClientException
     * @throws \Battleships\Exception\ApiClientException
     */
    protected function call($request, $method, \stdClass $data = null)
    {
        $jsonResponse = $this->oHttpClient->call($request, $method, $data);
        $response = Misc::jsonDecode($jsonResponse);

        $callInfo = $this->oHttpClient->getCallInfo();
        if ($callInfo['http_code'] == 404) {
            throw new ApiClientException($response->message, $response->code);
        }

        return $response;
    }
}
