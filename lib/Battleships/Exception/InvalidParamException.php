<?php

namespace Battleships\Exception;

/**
 * Invalid Parameter Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class InvalidParamException extends \Exception
{
    protected $code = 120;

    /**
     * @param string $invalidParamName
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($invalidParamName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid parameter: %s", $invalidParamName);

        parent::__construct($message, $code, $previous);
    }
}
