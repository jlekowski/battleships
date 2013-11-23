<?php

/**
 * BattleshipsCliInterface class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.4
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class BattleshipsCliInterface
{
    private $outputs = array();
    private $oBattleshipsGame;
    private $runInterface;

    public function __construct(BattleshipsClient $oBattleshipsClient)
    {
        $this->oBattleshipsClient = $oBattleshipsClient;
    }

    public function run()
    {
        $this->runInterface = true;
        while ($this->runInterface) {
            $command = "";

            if (empty($this->oBattleshipsGame)) {
                $command = "initGame";
            } else if (count($this->oBattleshipsGame->getPlayerShips()) == 0) {
                $command = "startGame";
            } else if ($this->oBattleshipsGame->getOtherStarted() && $this->oBattleshipsGame->isMyTurn()) {
                $command = "addShot";
            } else {
                $command = "update";
            }

            $this->runCommand($command);
        }
    }

    public function runCommand($command)
    {
        switch ($command) {
            case "show":
                $result = $this->runCommandShow();
                break;

            case "nameUpdate":
                $result = $this->runCommandNameUpdate();
                break;

            case "initGame":
                $result = $this->runCommandInitGame();
                break;

            case "startGame":
                $result = $this->runCommandStartGame();
                break;

            case "addShot":
                $result = $this->runCommandAddShot();
                break;

            case "update":
                $result = $this->runCommandUpdate();
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
                $this->runInterface = false;
                $this->outputsAppend("exit interface");
                break;

            default:
                $this->outputsAppend("show - show the battleground");
                $this->outputsAppend("exit - exit the game");
                break;
        }

        $this->outputsPrint();
        return $result;
    }

    private function runCommandShow()
    {
        $output = $this->showBattlegroud();
        $this->outputsAppend($output);
    }

    private function runCommandNameUpdate()
    {
        $playerName = $this->getInput("What's your name?");
        $this->oBattleshipsClient->updateName($this->oBattleshipsGame, $playerName);
        $this->runCommandShow();
    }

    private function runCommandInitGame()
    {
        $hash = $this->getInput("Provide game hash or press ENTER to start a new game");
        $this->oBattleshipsGame = $this->oBattleshipsClient->getGame($hash);
        $this->runCommandShow();

        if (!$hash) {
            $output = $this->oBattleshipsGame->getPlayerName() . " hash is: " . $this->oBattleshipsGame->getPlayerHash();
            $this->outputsAppend($output);
        }

        if ($this->oBattleshipsGame->getOtherHash()) {
            $output = $this->oBattleshipsGame->getOtherName() . " hash is: " . $this->oBattleshipsGame->getOtherHash();
            $this->outputsAppend($output);
        }
    }

    private function runCommandStartGame()
    {
        $ships = strtoupper($this->getInput("Set your ships"));
        $result = $this->oBattleshipsClient->startGame($this->oBattleshipsGame, $ships);
        $this->runCommandShow();
        if (!$result) {
            $output = "Ships set incorrectly";
            $this->outputsAppend($output);
        }
    }

    private function runCommandAddShot()
    {
        $shot = strtoupper($this->getInput("Shoot"));
        $result = $this->oBattleshipsClient->addShot($this->oBattleshipsGame, $shot);
        $this->runCommandShow();
        $output = $result ? $shot . ": " . $result : "Incorrect shot";
        $this->outputsAppend($output);
    }

    private function runCommandUpdate()
    {
        $oldOtherName = $this->oBattleshipsGame->getOtherName();
        echo PHP_EOL . "Waiting for " . $oldOtherName . "...";

        do {
            $result = $this->oBattleshipsClient->getUpdates($this->oBattleshipsGame);
            echo ".";
        } while ($result == false);

        $this->runCommandShow();
        foreach ($result as $action => $updates) {
            foreach ($updates as $update) {
                switch ($action) {
                    case "name_update":
                        $output = $oldOtherName . " changed name to: " . $this->oBattleshipsGame->getOtherName();
                        $this->outputsAppend($output);
                        break;

                    case "start_game":
                        $output = $this->oBattleshipsGame->getOtherName() . " started the game";
                        $this->outputsAppend($output);
                        break;

                    case "join_game":
                        $output = $this->oBattleshipsGame->getOtherName() . " joined the game";
                        $this->outputsAppend($output);
                        break;

                    case "shot":
                        $output = $this->oBattleshipsGame->getOtherName() . " shot " . $update;
                        $this->outputsAppend($output);
                        break;

                    case "chat":
                        break;
                }
            }
        }
    }

    private function showBattlegroud()
    {
        $marks = array('ship' => "S", 'miss' => ".", 'hit' => "x", 'sunk' => "X");
        $battle = $this->oBattleshipsGame->battle;
        $board  = sprintf("\n     % 39.39s        % 39.39s \n\n", $this->oBattleshipsGame->getPlayerName(), $this->oBattleshipsGame->getOtherName());
        $board .= "    ";

        // 11 rows (first row for X axis labels)
        for ($i = 0; $i < 11; $i++) {
            // 11 divs/columns in each row (first column for Y axis labels)
            for ($j = 0; $j < 11; $j++) {
                if ($i == 0 && $j > 0) {
                    $text = Battleships::$axisY[($j - 1)];
                    $board .= sprintf(" % 2s ", $text);
                } else if ($j == 0 && $i > 0) {
                    $text = Battleships::$axisX[($i - 1)];
                    $board .= sprintf(" % 2s |", $text);
                } else if ($j > 0 && $i > 0) {
                    $coords = Battleships::$axisY[($j - 1)] . Battleships::$axisX[($i - 1)];
                    $text = array_key_exists($coords, $battle['playerGround']) ? $marks[ $battle['playerGround'][$coords] ] : "";
                    $board .= sprintf(" % 1s |", $text);
                }
            }

            $board .= "  ";
            for ($j = 0; $j < 11; $j++) {
                if ($i == 0 && $j > 0) {
                    if ($j == 1) {
                        $board .= "     ";
                    }
                    $text = Battleships::$axisY[($j - 1)];
                    $board .= sprintf(" % 2s ", $text);
                } else if ($j == 0 && $i > 0) {
                    $text = Battleships::$axisX[($i - 1)];
                    $board .= sprintf(" % 2s |", $text);
                } else if ($j > 0 && $i > 0) {
                    $coords = Battleships::$axisY[($j - 1)].Battleships::$axisX[($i - 1)];
                    $text = array_key_exists($coords, $battle['otherGround']) ? $marks[ $battle['otherGround'][$coords] ] : "";
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
