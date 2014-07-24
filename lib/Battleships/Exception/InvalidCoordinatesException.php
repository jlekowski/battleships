<?php

namespace Battleships\Exception;

class InvalidCoordinatesException extends InvalidShipsException
{
    protected $code = 161;

    public function __construct($coords = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid coordinates provided: %s", $coords);

        parent::__construct($message, $code, $previous);
    }
}
