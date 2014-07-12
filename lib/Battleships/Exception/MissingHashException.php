<?php

namespace Battleships\Exception;

class MissingHashException extends InvalidHashException
{
    protected $code = 141;
}
