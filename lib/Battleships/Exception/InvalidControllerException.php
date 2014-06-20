<?php

namespace Battleships\Exception;

class InvalidControllerException extends \Exception
{
    protected $code = 100;

    public function __construct($invalidControllerName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid controller: %s", $invalidControllerName);

        parent::__construct($message, $code, $previous);
    }
}
