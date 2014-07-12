<?php

namespace Battleships\Exception;

class InvalidHashException extends \Exception
{
    protected $code = 140;

    public function __construct($invalidHash = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid hash provided: %s", $invalidHash);

        parent::__construct($message, $code, $previous);
    }
}
