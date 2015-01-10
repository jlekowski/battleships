<?php

namespace Battleships\Exception;

/**
 * Invalid Controller Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class InvalidControllerException extends \Exception
{
    protected $code = 100;

    /**
     * @param string $invalidControllerName
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($invalidControllerName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid controller: %s", $invalidControllerName);

        parent::__construct($message, $code, $previous);
    }
}
