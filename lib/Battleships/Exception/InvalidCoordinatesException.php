<?php

namespace Battleships\Exception;

/**
 * Invalid Coordinates Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class InvalidCoordinatesException extends InvalidShipsException
{
    protected $code = 161;

    /**
     * @param string $coords
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($coords = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid coordinates provided: %s", $coords);

        parent::__construct($message, $code, $previous);
    }
}
