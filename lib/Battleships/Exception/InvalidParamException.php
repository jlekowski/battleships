<?php

namespace Battleships\Exception;

class InvalidParamException extends \Exception
{
    protected $code = 120;

    public function __construct($invalidParamName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid parameter: %s", $invalidParamName);

        parent::__construct($message, $code, $previous);
    }
}
