<?php

namespace Battleships;

/**
 * Battleships\ClientInterface interface
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
interface ClientInterface
{
    /**
     * Create game
     * @param string $name
     * @return \Battleships\Game\Data
     */
    public function createGame($name);

    /**
     * Get game
     * @param string $hash
     * @return \Battleships\Game\Data
     */
    public function getGame($hash);

    /**
     * Update name
     * @param \Battleships\Game\Data $oData
     * @param string $playerName
     */
    public function updateName(\Battleships\Game\Data $oData, $playerName);

    /**
     * Add ships to start game
     * @param \Battleships\Game\Data $oData
     * @param array $ships
     */
    public function addShips(\Battleships\Game\Data $oData, array $ships);

    /**
     * Add shot
     * @param \Battleships\Game\Data $oData
     * @param string $coords
     * @return string miss|hit|sunk
     */
    public function addShot(\Battleships\Game\Data $oData, $coords);

    /**
     * Add chat
     * @param \Battleships\Game\Data $oData
     * @param string $text
     */
    public function addChat(\Battleships\Game\Data $oData, $text);

    /**
     * Get updates
     * @param \Battleships\Game\Data $oData
     * @return array
     */
    public function getUpdates(\Battleships\Game\Data $oData);
}
