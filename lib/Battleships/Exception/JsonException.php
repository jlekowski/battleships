<?php

namespace Battleships\Exception;

class JsonException extends \Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $code = $code !== 0 ? $code : json_last_error();
        $message = $message !== null ? $message : $this->getErrorMsg($code);

        parent::__construct($message, $code, $previous);
    }

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
