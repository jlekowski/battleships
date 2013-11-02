<?php

/**
 * BattleshipsClient class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.3
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class BattleshipsClient
{
    /**
     *
     * @var string
     */
    private $error;

    /**
     * Get Error
     * @return string Error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set Error
     * @param string $error Error to be set
     * @return void
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    public function __construct()
    {
        try {
            $this->soapClient = new SoapClient(WSDL_URL, array('cache_wsdl' => WSDL_CACHE_NONE, 'trace' => true));
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }
    }

    /**
     *
     * @param type $hash
     * @return BattleshipsGame
     */
    public function getGame($hash)
    {
        try {
            $game = $this->soapClient->getGame($hash);
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }

        $oBattleshipsGame = new BattleshipsGame($game);

        return $oBattleshipsGame;
    }

    public function updateName(BattleshipsGame &$oBattleshipsGame, $playerName)
    {
        try {
            $result = $this->soapClient->updateName($oBattleshipsGame->getPlayerHash(), $playerName);
            if ($result) {
                $oBattleshipsGame->setPlayerName($playerName);
            }
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }
    }

    public function startGame(BattleshipsGame &$oBattleshipsGame, $ships)
    {
        try {
            $result = $this->soapClient->startGame($oBattleshipsGame->getPlayerHash(), $ships);
            if ($result) {
                $oBattleshipsGame->setPlayerShips($ships);
                $ships_array = explode(",", $ships);
                foreach ($ships_array as $value) {
                    $oBattleshipsGame->battle['playerGround'][$value] = "ship";
                }
            }
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }

        return $result;
    }

    public function addShot(BattleshipsGame &$oBattleshipsGame, $coords)
    {
        try {
            $result = $this->soapClient->addShot($oBattleshipsGame->getPlayerHash(), $coords);
            if ($result) {
                $oBattleshipsGame->appendPlayerShots($coords);
                $oBattleshipsGame->battle['otherGround'][$coords] = $result;
                $whoseTurn = $result == "miss" ? $oBattleshipsGame->getOtherNumber() : $oBattleshipsGame->getPlayerNumber();
                $oBattleshipsGame->setWhoseTurn($whoseTurn);
            }
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }

        return $result;
    }

    public function getUpdates(BattleshipsGame &$oBattleshipsGame)
    {
        try {
            $result = $this->soapClient->getUpdates($oBattleshipsGame->getPlayerHash(), $oBattleshipsGame->getLastIdEvents());
        }
        catch (SoapFault $e) {
            $this->setError($e->getMessage());
        }

        foreach ($result as $action => $updates) {
            foreach ($updates as $update) {
                $this->applyUpdate($oBattleshipsGame, $update, $action);
            }
        }

        return $result;
    }

    private function applyUpdate(BattleshipsGame &$oBattleshipsGame, $update, $action)
    {
        switch ($action) {
            case "name_update":
                $oBattleshipsGame->setOtherName($update);
                break;

            case "start_game":
                $oBattleshipsGame->setOtherStarted(true);
                break;

            case "join_game":
                $oBattleshipsGame->setOtherJoined(true);
                break;

            case "shot":
                $oBattleshipsGame->appendOtherShots($update);

                if (in_array($update, $oBattleshipsGame->getPlayerShips())) {
                    $shotResult = "hit";
                    $whoseTurn = $oBattleshipsGame->getOtherNumber();
                }
                else {
                    $shotResult = "miss";
                    $whoseTurn = $oBattleshipsGame->getPlayerNumber();
                }

                $oBattleshipsGame->battle['playerGround'][ $update ] = $shotResult;
                $oBattleshipsGame->setWhoseTurn($whoseTurn);
                break;

            case "chat":
                break;

            case "lastIdEvents":
                $oBattleshipsGame->setLastIdEvents($update);
                break;
        }
    }
}
