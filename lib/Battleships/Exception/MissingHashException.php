<?php

namespace Battleships\Exception;

/**
 * Missing Hash Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class MissingHashException extends InvalidHashException
{
    protected $code = 141;
}
