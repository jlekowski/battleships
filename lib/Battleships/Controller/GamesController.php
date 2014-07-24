<?php

namespace Battleships\Controller;

use Battleships\DB;
use Battleships\Game\Data;
use Battleships\Game\Manager;
use Battleships\Game\Formatter;
use Battleships\Misc;
use Battleships\Exception\InvalidHashException;
use Battleships\Exception\MissingHashException;

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
                throw new MissingHashException();
            }

            $this->oManager->initGame($hash);
        }
    }

    public function addAction()
    {
        $this->oManager->initGame();
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
        $this->oManager->updateName($this->data->name);

        $this->result = true;
    }

    public function addChatsAction()
    {
        $this->oManager->addChat($this->data->text);

        $this->result = Misc::getUtcTime()->format("Y-m-d H:i:s");
    }

    public function addShipsAction()
    {
        $ships = strtoupper(implode(",", $this->data->ships));
        $this->oManager->startGame($ships);

        $this->result = true;
    }

    public function addShotsAction()
    {
        $coords = strtoupper($this->data->shot);
        $shotResult = $this->oManager->addShot($coords);

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
