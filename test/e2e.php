<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init" . DIRECTORY_SEPARATOR . "bootstrap.php";

use Battleships\Misc;

try {
    $baseUrl = "http://battleships.localhost";
//    $baseUrl .= "/server.php?";
    $apiRequest = new ApiRequest($baseUrl);
    // initiate game
    $game = $apiRequest->initGame();
    // get game
    $apiRequest->getGame($game);
    // update name
    $apiRequest->updateName($game);
    // add ships
    $apiRequest->addShips($game);
    // add chats
    $apiRequest->addChats($game);
    // getUpdates
    $apiRequest->getUpdates($game);


} catch (Exception $e) {
    print_r($game);
    exit("ERROR: " . $e->getMessage() . PHP_EOL);
}

print_r($game);
exit("OK\n");

class ApiRequest
{
    private $baseUrl;
    private $ch;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    public function initGame()
    {
        $nameData = new stdClass();
        $nameData->name = "New Test Player";
        $oRequestDetails = new RequestDetails("/games", "POST", $nameData);
        $game = $this->call($oRequestDetails);
        $this->validateGame($game);

        return $game;
    }

    private function validateGame(stdClass $game)
    {
        if (empty($game->playerHash)) {
            throw new Exception("No player hash");
        }

        if (empty($game->otherHash)) {
            throw new Exception("No other hash");
        }

        if (empty($game->playerName)) {
            throw new Exception("No player name");
        }

        if (empty($game->otherName)) {
            throw new Exception("No other name");
        }

        if ($game->playerNumber !== 1) {
            throw new Exception("Incorrect player number: " . $game->playerNumber);
        }

        if ($game->otherNumber !== 2) {
            throw new Exception("Incorrect other number: " . $game->otherNumber);
        }

        if ($game->lastIdEvents !== 0) {
            throw new Exception("Incorrect last id event: " . $game->lastIdEvents);
        }

        if ($game->whoseTurn !== 1) {
            throw new Exception("Incorrect whose turn: " . $game->whoseTurn);
        }
    }

    public function getGame(stdClass &$game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "GET");
        $gameData = $this->call($oRequestDetails);
        $this->validateGameDetails($gameData, $game);
        $game = $gameData;

        return true;
    }

    private function validateGameDetails(stdClass $gameData, stdClass $game)
    {
        foreach ($game as $key => $value) {
            if (!isset($gameData->$key) || $gameData->$key !== $value) {
                throw new Exception("Incorrect property value: (" . $key . ": " . $value . " - " . $gameData->$key . ")");
            }
        }

        if ($gameData->playerShips !== array()) {
            throw new Exception("Incorrect player ships: " . print_r($gameData->playerShips, true));
        }

        if ($gameData->otherJoined !== false) {
            throw new Exception("Incorrect other joined: " . $gameData->otherJoined);
        }

        if ($gameData->otherStarted !== false) {
            throw new Exception("Incorrect other started: " . $gameData->otherStarted);
        }

        $emptyBattle = new stdClass();
        $emptyBattle->playerGround = array();
        $emptyBattle->otherGround = array();
        if ($gameData->battle != $emptyBattle) {
            throw new Exception("Incorrect battle: " . print_r($gameData->battle, true));
        }

        if ($gameData->chats !== array()) {
            throw new Exception("Incorrect chats: " . print_r($gameData->chats, true));
        }
    }

    public function updateName(stdClass $game)
    {
        $nameData = new stdClass();
        $nameData->name = "Updated Name";
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "PUT", $nameData);
        $result = $this->call($oRequestDetails);
        $this->validateTrueResult($result, __FUNCTION__);
        $game->playerName = $nameData->name;

        return $result;
    }

    public function addShips(stdClass $game)
    {
        $shipsData = new stdClass();
        $shipsData->ships = array("A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10");
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/ships", "POST", $shipsData);
        $result = $this->call($oRequestDetails);
        $this->validateTrueResult($result, __FUNCTION__);
        $game->playerShips = $shipsData->ships;

        return $result;
    }

    public function addChats(stdClass $game)
    {
        $chatData = new stdClass();
        $chatData->chat = "Test chat";
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/chats", "POST", $chatData);
        $result = $this->call($oRequestDetails);
        $this->validateDate($result);
        $game->chats[] = array(
            'name' => $game->playerName,
            'text' => $chatData->chat,
            'time' => $result
        );

        return $result;
    }

    public function validateDate($date)
    {
        if (!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $date)) {
            throw new Exception("Incorrect chat date: " . $date);
        }
    }

    public function getUpdates(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/updates/" . $game->lastIdEvents, "GET");
        $result = $this->call($oRequestDetails);
        $this->validateEmptyArray($result);

        return $result;
    }

    private function validateEmptyArray($array)
    {
        if ($array !== array()) {
            throw new Exception("Incorrect update info: " . print_r($array, true));
        }
    }

    private function validateTrueResult($result, $methodName)
    {
        if ($result !== true) {
            throw new Exception("Incorrect " . $methodName . " response: " . $result);
        }
    }

    private function call(RequestDetails $oRequestDetails)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $oRequestDetails->getMethod());
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $oRequestDetails->getData());
        curl_setopt($this->ch, CURLOPT_URL, $this->baseUrl . $oRequestDetails->getRequest());

        $curlResponse = curl_exec($this->ch);
        $response = Misc::jsonDecode($curlResponse);
        if ($response->error !== null) {
            throw new Exception(print_r($response->error, true));
        }

        return $response->result;
    }
}

class RequestDetails
{
    private $request;
    private $method;
    private $data;

    public function __construct($request, $method, $data = null)
    {
        $this->request = $request;
        $this->method = strtoupper($method);
        $this->data = $data;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getData()
    {
        return json_encode($this->data);
    }

    public function getRequest()
    {
        return $this->request;
    }
}
