<?php

namespace Battleships;

use Battleships\Game\Manager;

/**
 * Battleships\CliView class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 * @todo       Ships and shots to be comma separated or array?
 */
class CliView
{
    private $outputs = array();
    private $oClient;
    /**
     * @var \Battleships\Game\Data
     */
    private $oData;
    private $runView;

    /**
     * @param \Battleships\ClientInterface $oClient
     */
    public function __construct(ClientInterface $oClient)
    {
        $this->oClient = $oClient;
    }

    public function run()
    {
        $this->runView = true;
        while ($this->runView) {
            if (empty($this->oData)) {
                $command = "initGame";
            } elseif (count($this->oData->getPlayerShips()) == 0) {
                $command = "addShips";
            } elseif ($this->oData->getOtherStarted() && $this->oData->isMyTurn()) {
                $command = "addShot";
            } else {
                $command = "getUpdates";
            }

            try {
                $this->runCommand($command);
            } catch (\Exception $e) {
                Misc::log($e);
                if (!empty($this->oData)) {
                    $this->runCommandShow();
                }
                $output = $e->getMessage();
                $this->outputsAppend($output);
            }
            $this->outputsPrint();
        }
    }

    private function runCommand($command)
    {
        switch ($command) {
            case "show":
                $this->runCommandShow();
                break;

            case "nameUpdate":
                $this->runCommandNameUpdate();
                break;

            case "initGame":
                $this->runCommandInitGame();
                break;

            case "addShips":
                $this->runCommandAddShips();
                break;

            case "addShot":
                $this->runCommandAddShot();
                break;

            case "getUpdates":
                $this->runCommandGetUpdates();
                break;

            case "":
            case "help":
            case "?":
                $this->outputsAppend("help|? - this screen");
                $this->outputsAppend("show   - show the battleground");
                $this->outputsAppend("exit   - exit the game");
                break;

            case "q":
            case "exit":
                $this->runView = false;
                $this->outputsAppend("exit CLI view");
                break;

            default:
                $this->outputsAppend("show - show the battleground");
                $this->outputsAppend("exit - exit the game");
                break;
        }
    }

    private function runCommandShow()
    {
        $output = $this->getBattleground();
        $this->outputsAppend($output);
    }

    private function runCommandNameUpdate()
    {
        $playerName = $this->getInput("What's your name?");
        $this->oClient->updateName($this->oData, $playerName);
        $this->runCommandShow();
    }

    private function runCommandInitGame()
    {
        $hash = $this->getInput("Provide game hash or press ENTER to start a new game");
        if ($hash === "") {
            $name = $this->getInput("What's your name?");
            $this->oData = $this->oClient->createGame($name);
            if ($this->oData->getPlayerName() !== $name) {
                $this->oClient->updateName($this->oData, $name);
            }
        } else {
            $this->oData = $this->oClient->getGame($hash);
        }

        $this->runCommandShow();

        $output = $this->oData->getPlayerName() . " hash is: " . $this->oData->getPlayerHash();
        $this->outputsAppend($output);

        if ($this->oData->getOtherHash()) {
            $output = $this->oData->getOtherName() . " hash is: " . $this->oData->getOtherHash();
            $this->outputsAppend($output);
        }
    }

    private function runCommandAddShips()
    {
        $ships = strtoupper($this->getInput("Set your ships"));
        $this->oClient->addShips($this->oData, explode(",", $ships));
        $this->runCommandShow();
    }

    private function runCommandAddShot()
    {
        $shot = strtoupper($this->getInput("Shoot"));
        $result = $this->oClient->addShot($this->oData, $shot);
        $this->runCommandShow();
        $output = $shot . ": " . $result;
        $this->outputsAppend($output);
    }

    private function runCommandGetUpdates()
    {
        $oldOtherName = $this->oData->getOtherName();
        echo PHP_EOL . "Waiting for " . $oldOtherName . "...";

        do {
            $result = $this->oClient->getUpdates($this->oData);
            echo ".";
        } while ($result == false);

        $this->runCommandShow();
        foreach ($result as $action => $updates) {
            foreach ($updates as $update) {
                switch ($action) {
                    case "name_update":
                        $output = $oldOtherName . " changed name to: " . $this->oData->getOtherName();
                        break;

                    case "start_game":
                        $output = $this->oData->getOtherName() . " started the game";
                        break;

                    case "join_game":
                        $output = $this->oData->getOtherName() . " joined the game";
                        break;

                    case "shot":
                        $output = $this->oData->getOtherName() . " shot " . $update;
                        break;

                    case "chat":
                        $output = $this->oData->getOtherName() . " sent message: " . $update->text;
                        break;

                    default:
                        $output = "";
                        break;
                }

                if ($output != "") {
                    $this->outputsAppend($output);
                }
            }
        }
    }

    private function getBattleground()
    {
        $marks = array('ship' => "S", 'miss' => ".", 'hit' => "x", 'sunk' => "X");
        $battle = $this->oData->battle;
        $board  = sprintf(
            "\n     % 39.39s        % 39.39s \n\n",
            $this->oData->getPlayerName(),
            $this->oData->getOtherName()
        );
        $board .= "    ";

        // 11 rows (first row for X axis labels)
        for ($i = 0; $i < 11; $i++) {
            // 11 divs/columns in each row (first column for Y axis labels)
            for ($j = 0; $j < 11; $j++) {
                if ($i == 0 && $j > 0) {
                    $text = Manager::$axisX[($j - 1)];
                    $board .= sprintf(" % 2s ", $text);
                } elseif ($j == 0 && $i > 0) {
                    $text = Manager::$axisY[($i - 1)];
                    $board .= sprintf(" % 2s |", $text);
                } elseif ($j > 0 && $i > 0) {
                    $coords = Manager::$axisY[($i - 1)] . Manager::$axisX[($j - 1)];
                    $text = isset($battle->playerGround->{$coords})
                        ? $marks[ $battle->playerGround->{$coords} ]
                        : "";
                    $board .= sprintf(" % 1s |", $text);
                }
            }

            $board .= "  ";
            for ($j = 0; $j < 11; $j++) {
                if ($i == 0 && $j > 0) {
                    if ($j == 1) {
                        $board .= "     ";
                    }
                    $text = Manager::$axisX[($j - 1)];
                    $board .= sprintf(" % 2s ", $text);
                } elseif ($j == 0 && $i > 0) {
                    $text = Manager::$axisY[($i - 1)];
                    $board .= sprintf(" % 2s |", $text);
                } elseif ($j > 0 && $i > 0) {
                    $coords = Manager::$axisY[($i - 1)] . Manager::$axisX[($j - 1)];
                    $text = isset($battle->otherGround->{$coords})
                        ? $marks[ $battle->otherGround->{$coords} ]
                        : "";
                    $board .= sprintf(" % 1s |", $text);
                }
            }
            $board .= "\n    ".str_repeat("+---", 10)."+";
            $board .= "      ".str_repeat("+---", 10)."+\n";
        }

        return $board;
    }

    private function outputsAppend($output)
    {
        $this->outputs[] = $output;
    }

    private function outputsPrint()
    {
        echo PHP_EOL . implode(PHP_EOL, $this->outputs) . PHP_EOL;
        $this->outputs = array();
    }

    public function getInput($prompt = "", $default = "")
    {
        $predefined = array('y' => true, 'n' => false, '' => $default);

        echo PHP_EOL . $prompt . PHP_EOL . "> ";
        $input = trim(fgets(STDIN));

        return array_key_exists(strtolower($input), $predefined) ? $predefined[strtolower($input)] : $input;
    }
}
