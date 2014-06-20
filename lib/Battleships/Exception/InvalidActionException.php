<?php

namespace Battleships\Exception;

class InvalidActionException extends \BadMethodCallException
{
    protected $code = 110;

    public function __construct($invalidActionName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid action: %s", $invalidActionName);

        parent::__construct($message, $code, $previous);
    }
}
