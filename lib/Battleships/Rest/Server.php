<?php

namespace Battleships\Rest;

use Battleships\Game\Manager;
use Battleships\Misc;

/**
 * Battleships\Rest\Server class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class Server
{
    /**
     * @var Battleships\Game\Manager
     */
    private $oManager;
    /**
     * @var array
     */
    private $requestParams;
    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @param \Battleships\Game\Manager $oManager
     */
    public function __construct(Manager $oManager)
    {
        $this->oManager = $oManager;
    }

    public function run()
    {
        $this->parseRequest();
        echo "<pre>" . print_r($this, 1);
        exit;

        $response = ['test' => 1];
        echo json_encode($response);
    }

    private function runRequest()
    {
        $controller = isset($this->requestParams[0]) ? $this->requestParams[0] : null;
        switch ($controller) {
            case "games":
                $this->runGamesRequest();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }

    private function runGamesRequest()
    {
        $action = isset($this->requestParams[2]) ? $this->requestParams[2] : null;

        switch ($action) {
            case null:
                $this->runGamesAction();
                break;

            case "chats":
                $this->runChatsAction();
                break;

            case "shots":
                $this->runShotsAction();
                break;

            case "updates":
                $this->runUpdatesAction();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }

    private function runGamesAction()
    {
        switch ($this->requestMethod) {
            case "GET":
                $this->getGame();
                break;

            case "POST":
                $this->startGame();
                break;

            case "PUT":
                $this->updateName();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }

    private function runChatsAction()
    {
        switch ($this->requestMethod) {
            case "POST":
                $this->addChat();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }

    private function runShotsAction()
    {
        switch ($this->requestMethod) {
            case "POST":
                $this->addShot();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }

    private function runUpdatesAction()
    {
        switch ($this->requestMethod) {
            case "GET":
                $this->getUpdates();
                break;

            default:
                throw new Exception("Incorrect request provided");
        }
    }


    private function parseRequest()
    {
        $urlInfo = pathinfo($_SERVER['PHP_SELF']);
        // remove public folder from URL but only once (in case it's "/")
        $routeParams = preg_replace("#^" . $urlInfo['dirname'] ."(/?)#", "", $_SERVER['REQUEST_URI'], 1);
        $this->requestParams = explode("/", $routeParams);
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
    }

    private function getGame()
    {
        $hash = isset($this->requestParams[1]) ? $this->requestParams[1] : "";
        $timezoneOffset = 0;

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

    private function updateName()
    {
        if (!isset($this->requestParams[1])) {
            throw new Exception("No hash provided");
        }
        $playerName = "New Name Test";
        $hash = $this->requestParams[1];
        $this->oManager->initGame($hash);

        return $this->oManager->updateName($playerName);
    }

    private function startGame()
    {
        if (!isset($this->requestParams[1])) {
            throw new Exception("No hash provided");
        }
        $ships = [];
        $hash = $this->requestParams[1];
        $this->oManager->initGame($hash);

        return $this->oManager->startGame(strtoupper($ships));
    }

    private function addShot()
    {
        if (!isset($this->requestParams[1])) {
            throw new Exception("No hash provided");
        }
        $hash = $this->requestParams[1];
        $this->oManager->initGame($hash);
        $coords = "A1";

        return $this->oManager->addShot(strtoupper($coords));
    }

    private function getUpdates()
    {
        if (!isset($this->requestParams[1])) {
            throw new Exception("No hash provided");
        }
        $hash = $this->requestParams[1];
        $this->oManager->initGame($hash);
        $lastIdEvents = 1;
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

    private function addChat()
    {
        if (!isset($this->requestParams[1])) {
            throw new Exception("No hash provided");
        }
        $hash = $this->requestParams[1];
        $this->oManager->initGame($hash);
        $text = "Text TEST";
        $result = $this->oManager->addChat($text)
            ? Misc::getUtcTime()->modify($this->oManager->oData->getTimezoneOffset() . "hour")->format("Y-m-d H:i:s")
            : "";

        return $result;
    }
}
