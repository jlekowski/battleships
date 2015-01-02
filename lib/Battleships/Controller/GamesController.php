<?php

namespace Battleships\Controller;

use Battleships\Game\Formatter;
use Battleships\Misc;
use Battleships\Exception\MissingHashException;

/**
 * API Game Controller
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class GamesController extends AbstractController
{
    /**
     * @throws MissingHashException
     */
    public function init()
    {
        // hash is not required when a new game starts
        if ($this->actionName !== "add") {
            $hash = $this->getParam('controllerParam');
            if (empty($hash)) {
                throw new MissingHashException();
            }

            $this->oManager->initGame($hash);
        }
    }

    /**
     * Create new game
     */
    public function addAction()
    {
        $this->oManager->initGame();
        $oFormatter = new Formatter($this->oManager);

        $this->result = $oFormatter->getForGame(true);
    }

    /**
     * Get game
     */
    public function getAction()
    {
        $oFormatter = new Formatter($this->oManager);

        $this->result = $oFormatter->getForGame();
    }

    /**
     * Update game
     */
    public function updateAction()
    {
        $this->oManager->updateName($this->data->name);
    }

    /**
     * Add chat
     */
    public function addChatsAction()
    {
        $this->oManager->addChat($this->data->text);

        $this->result = Misc::getUtcTime()->getTimestamp();
    }

    /**
     * Add ship
     * @throws \Battleships\Exception\InvalidShipsException
     */
    public function addShipsAction()
    {
        $ships = strtoupper(implode(",", $this->data->ships));
        $this->oManager->startGame($ships);
    }

    /**
     * Add shot
     * @throws \Battleships\Exception\GameFlowException
     */
    public function addShotsAction()
    {
        $coords = strtoupper($this->data->shot);
        $shotResult = $this->oManager->addShot($coords);

        $this->result = $shotResult;
    }

    /**
     * Get game updates
     */
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
