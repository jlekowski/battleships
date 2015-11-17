<?php

namespace Battleships\Exception;

/**
 * Invalid Hash Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class InvalidHashException extends \Exception
{
    protected $code = 140;

    /**
     * @param string $invalidHash
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($invalidHash = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid hash provided: %s", $invalidHash);

        parent::__construct($message, $code, $previous);
    }
}
