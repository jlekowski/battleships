<?php

namespace Battleships\Controller;

use Battleships\DB;
use Battleships\Game\Data;
use Battleships\Game\Manager;
use Battleships\Game\Formatter;
use Battleships\Misc;

class GamesController extends Controller
{
    protected $oManager;

    public function init()
    {
        $this->oManager = new Manager(new Data(), new DB(DB_TYPE));

        // hash is not required when a new game starts
        if ($this->actionName !== "add") {
            $hash = $this->getParam('controllerParam');
            if (empty($hash)) {
                throw new \Exception("No hash provided");
            }

            $gameInitiated = $this->oManager->initGame($hash);
            if ($gameInitiated === false) {
                throw new \Exception("Invalid hash provided");
            }
        }
    }

    public function addAction()
    {
        $gameInitiated = $this->oManager->initGame();
        if ($gameInitiated === false) {
            throw new \Exception("Game could not be initiated");
        }

        $oFormatter = new Formatter($this->oManager);

        $this->result = $oFormatter->getForGame(true);
    }

    public function getAction()
    {
        $oFormatter = new Formatter($this->oManager);

        $this->result = $oFormatter->getForGame();
    }

    public function updateAction()
    {
        $nameUpdated = $this->oManager->updateName($this->data->name);
        if ($nameUpdated === false) {
            throw new \Exception("Name could not be added");
        }

        $this->result = $nameUpdated;
    }

    public function addChatsAction()
    {
        $chatAdded = $this->oManager->addChat($this->data->chat);
        if ($chatAdded === false) {
            throw new \Exception("Chat could not be added");
        }

        $this->result = Misc::getUtcTime()->format("Y-m-d H:i:s");
    }

    public function addShipsAction()
    {
        $ships = strtoupper(implode(",", $this->data->ships));
        $gameStarted = $this->oManager->startGame($ships);
        if ($gameStarted === false) {
            throw new \Exception("Game could not be started");
        }

        $this->result = $gameStarted;
    }

    public function addShotsAction()
    {
        $coords = strtoupper($this->data->shot);
        $shotResult = $this->oManager->addShot($coords);
        if ($shotResult === false) {
            throw new \Exception("Shot could not be added");
        }

        $this->result = $shotResult;
    }

    public function getUpdatesAction()
    {
        $this->oManager->oData->setLastIdEvents($this->getParam('actionParam'));

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

        $this->result = $updates;
    }
}
