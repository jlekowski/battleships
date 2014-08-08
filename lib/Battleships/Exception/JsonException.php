<?php

namespace Battleships\Exception;

/**
 * JSON Exception
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class JsonException extends \Exception
{
    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        $code = $code !== 0 ? $code : json_last_error();
        $message = $message !== null ? $message : $this->getErrorMsg($code);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param int $code
     * @return string
     */
    private function getErrorMsg($code)
    {
        /* PHP >= 5.5 */
        if (function_exists('json_last_error_msg')) {
            $message = json_last_error_msg();
        } else {
            switch ($code) {
                case JSON_ERROR_NONE:
                    $message = "No error has occurred";
                    break;
                case JSON_ERROR_DEPTH:
                    $message = "The maximum stack depth has been exceeded";
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $message = "Invalid or malformed JSON";
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $message = "Control character error, possibly incorrectly encoded";
                    break;
                case JSON_ERROR_SYNTAX:
                    $message = "Syntax error";
                    break;
                case JSON_ERROR_UTF8:
                    $message = "Malformed UTF-8 characters, possibly incorrectly encoded";
                    break;
                default:
                    $message = "Unknown JSON error";
                    break;
            }
        }

        return $message;
    }
}
