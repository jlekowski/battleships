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
    // get updates
    $apiRequest->getUpdates($game);
    // get other game
    $otherGame = $apiRequest->getOtherGame($game);
    // add other ships
    $apiRequest->addShips($otherGame);
    // add shot
    $apiRequest->addShots($game);
    // get other updates
    $apiRequest->getOtherUpdates($otherGame);

} catch (Exception $e) {
    if (isset($game)) {
        print_r($game);
    }
    exit("ERROR: " . $e->getMessage() . PHP_EOL);
}

//print_r($game);
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
            throw new E2eException("No player hash");
        }

        if (empty($game->otherHash)) {
            throw new E2eException("No other hash");
        }

        if (empty($game->playerName)) {
            throw new E2eException("No player name");
        }

        if (empty($game->otherName)) {
            throw new E2eException("No other name");
        }

        if ($game->playerNumber !== 1) {
            throw new E2eException("Incorrect player number: " . $game->playerNumber);
        }

        if ($game->otherNumber !== 2) {
            throw new E2eException("Incorrect other number: " . $game->otherNumber);
        }

        if ($game->lastIdEvents !== 0) {
            throw new E2eException("Incorrect last id event: " . $game->lastIdEvents);
        }

        if ($game->whoseTurn !== 1) {
            throw new E2eException("Incorrect whose turn: " . $game->whoseTurn);
        }
    }

    public function getGame(stdClass &$game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "GET");
        $gameData = $this->call($oRequestDetails);
        $this->validateGameDetails($gameData, $game);
        $game = $gameData;
    }

    private function validateGameDetails(stdClass $gameData, stdClass $game)
    {
        foreach ($game as $key => $value) {
            if (!isset($gameData->$key) || $gameData->$key !== $value) {
                throw new E2eException("Incorrect property value: (" . $key . ": " . $value . " - " . $gameData->$key . ")");
            }
        }

        if ($gameData->playerShips !== array()) {
            throw new E2eException("Incorrect player ships: " . print_r($gameData->playerShips, true));
        }

        if (isset($gameData->otherShips)) {
            throw new E2eException("Other 2 ships should not be set: " . print_r($gameData->otherShips, true));
        }

        if ($gameData->otherJoined !== false) {
            throw new E2eException("Incorrect other joined: " . $gameData->otherJoined);
        }

        if ($gameData->otherStarted !== false) {
            throw new E2eException("Incorrect other started: " . $gameData->otherStarted);
        }

        $emptyBattle = new stdClass();
        $emptyBattle->playerGround = new stdClass();
        $emptyBattle->otherGround = new stdClass();
        if ($gameData->battle != $emptyBattle) {
            throw new E2eException("Incorrect battle: " . print_r($gameData->battle, true));
        }

        if ($gameData->chats !== array()) {
            throw new E2eException("Incorrect chats: " . print_r($gameData->chats, true));
        }
    }

    public function getOtherGame(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->otherHash, "GET");
        $gameData = $this->call($oRequestDetails);
        $this->validateOtherGameDetails($gameData, $game);

        return $gameData;
    }

    private function validateOtherGameDetails(stdClass $gameData, stdClass $game)
    {
        if ($gameData->playerName !== $game->otherName) {
            throw new E2eException(sprintf("Incorrect player 2 name: %s instead of %s", $gameData->playerName, $game->otherName));
        }

        if ($gameData->otherName !== $game->playerName) {
            throw new E2eException(sprintf("Incorrect other 2 name: %s instead of %s", $gameData->otherName, $game->playerName));
        }

        if ($gameData->playerHash !== $game->otherHash) {
            throw new E2eException(sprintf("Incorrect player 2 hash: %s instead of %s", $gameData->playerHash, $game->otherHash));
        }

        if ($gameData->otherHash !== "") {
            throw new E2eException(sprintf("Other 2 hash should be empty: %s instead", $gameData->otherHash));
        }

        if ($gameData->playerNumber !== 2) {
            throw new E2eException(sprintf("Incorrect player 2 number: %s instead of 2", $gameData->playerNumber));
        }

        if ($gameData->otherNumber !== 1) {
            throw new E2eException(sprintf("Incorrect other 2 number: %s instead of 1", $gameData->otherNumber));
        }

        if ($gameData->whoseTurn !== 1) {
            throw new E2eException(sprintf("Incorrect player 2 turn: %s instead of 1", $gameData->whoseTurn));
        }

        if ($gameData->playerShips !== array()) {
            throw new E2eException("Incorrect player 2 ships: " . print_r($gameData->playerShips, true));
        }

        if (isset($gameData->otherShips)) {
            throw new E2eException("Other 2 ships should not be set: " . print_r($gameData->otherShips, true));
        }

        if ($gameData->otherJoined !== true) {
            throw new E2eException("Incorrect other 2 joined: " . $gameData->otherJoined);
        }

        if ($gameData->otherStarted !== true) {
            throw new E2eException("Incorrect other 2 started: " . $gameData->otherStarted);
        }

        $emptyBattle = new stdClass();
        $emptyBattle->playerGround = new stdClass();
        $emptyBattle->otherGround = new stdClass();
        if ($gameData->battle != $emptyBattle) {
            throw new E2eException("Incorrect 2 battle: " . print_r($gameData->battle, true));
        }

        if (count($gameData->chats) !== 1) {
            throw new E2eException("Incorrect 2 number of chats: " . print_r(array($gameData->chats, $game->chats), true));
        }

        if (($gameData->chats[0]->player !== $game->chats[0]->player) || ($gameData->chats[0]->text !== $game->chats[0]->text)) {
            throw new E2eException("Incorrect 2 chats: " . print_r(array($gameData->chats, $game->chats), true));
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
        $chatData->text = "Test chat";
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/chats", "POST", $chatData);
        $result = $this->call($oRequestDetails);
        $this->validateDate($result);
        $chatData->player = $game->playerNumber;
        $chatData->timestamp = $result;
        $game->chats[] = $chatData;

        return $result;
    }

    public function addShots(stdClass $game)
    {
        $shotData = new stdClass();
        $shots = array('A1' => "sunk", 'C2' => "hit", 'D2' => "sunk", 'J10' => "miss");

        foreach ($shots as $shot => $expectedResult) {
            $shotData->shot = $shot;
            $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/shots", "POST", $shotData);
            $result = $this->call($oRequestDetails);
            $this->validateAddShots($result, $expectedResult);
        }
    }

    private function validateAddShots($result, $expected)
    {
        if ($result !== $expected) {
            throw new E2eException(sprintf("Incorrect shot result: %s instead of %s", $result, $expected));
        }
    }

    public function validateDate($date)
    {
        if (!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $date)) {
            throw new E2eException("Incorrect chat date: " . $date);
        }
    }

    public function getUpdates(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/updates/" . $game->lastIdEvents, "GET");
        $result = $this->call($oRequestDetails);
        $this->validateEmptyArray($result);
    }

    public function getOtherUpdates(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/updates/" . $game->lastIdEvents, "GET");
        $result = $this->call($oRequestDetails);
        $this->validateOtherGetUpdates($result, $game);
    }

    private function validateOtherGetUpdates(stdClass $result, stdClass $game)
    {
        if ($result->shot !== array("A1", "C2", "D2", "J10")) {
            throw new E2eException("Incorrect shot updates: " . print_r($result->shots, true));
        }

        if ($result->lastIdEvents[0] - $game->lastIdEvents !== 6) {
            throw new E2eException(sprintf("Incorrect number of events added: %s - %s", $result->lastIdEvents[0], $game->lastIdEvents));
        }
    }

    private function validateEmptyArray($array)
    {
        if ($array !== array()) {
            throw new E2eException("Incorrect update info: " . print_r($array, true));
        }
    }

    private function validateTrueResult($result, $methodName)
    {
        if ($result !== true) {
            throw new E2eException("Incorrect " . $methodName . " response: " . $result);
        }
    }

    private function call(RequestDetails $oRequestDetails)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $oRequestDetails->getMethod());
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $oRequestDetails->getData());
        curl_setopt($this->ch, CURLOPT_URL, $this->baseUrl . $oRequestDetails->getRequest());

        $curlResponse = curl_exec($this->ch);
        if ($curlResponse === false) {
            throw new E2eException(curl_error($this->ch));
        }
        $response = Misc::jsonDecode($curlResponse);
        if ($response->error !== null) {
            throw new E2eException(print_r($response->error, true));
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

class E2eException extends Exception {}
