<?php

namespace Battleships\Exception;

/**
 * Invalid Action Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class InvalidActionException extends \BadMethodCallException
{
    protected $code = 110;

    /**
     * @param string $invalidActionName
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($invalidActionName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid action: %s", $invalidActionName);

        parent::__construct($message, $code, $previous);
    }
}
