<?php

namespace Battleships;

use Battleships\Exception\JsonException;

/**
 * General functions
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.5
 *
 */
class Misc
{

    /**
     * Get current UTC time
     * @return \DateTime
     */
    public static function getUtcTime()
    {
        return new \DateTime("now", new \DateTimeZone("UTC"));
    }

    /**
     * Escape html output
     * @param string $text
     * @return string
     */
    public static function escapeString($text)
    {
        return htmlentities($text, ENT_COMPAT, "UTF-8");
    }

    /**
     * Check if current URL is root directory
     * @return bool
     */
    public static function isRootDir()
    {
        // TODO - SERVER as a param
        $urlInfo     = parse_url(self::getRequestedUrl());
        $urlPathInfo = pathinfo($urlInfo['path']);

        return $urlPathInfo['dirname'] == "/";
    }

    /**
     * Get URL to the SQLite DB file
     * @return string
     */
    public static function getSqliteUrl()
    {
        $urlInfo     = parse_url(self::getRequestedUrl());
        $urlPathInfo = pathinfo($urlInfo['path']);

        $dirInfo = explode("/", $urlPathInfo['dirname']);
        $lastIndex = count($dirInfo) - 1;
        if (($urlPathInfo['basename'] == $urlPathInfo['basename']) && !array_key_exists('extension', $urlPathInfo)) {
            $lastIndex += 1;
        }
        $dirInfo[$lastIndex] = "db";

        $directSqliteUrl = $urlInfo['scheme'] . "://" . $urlInfo['host'] . implode('/', $dirInfo) . "/" . SQLITE_FILE;

        return $directSqliteUrl;
    }

    public static function getRequestedUrl()
    {
        if (isset($_SERVER['SCRIPT_URI'])) {
            return $_SERVER['SCRIPT_URI'];
        }

        $protocol = $_SERVER['SERVER_PORT'] === "443" ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['DOCUMENT_URI'];

        return sprintf("%s://%s%s", $protocol, $host, $uri);
    }

    public static function jsonDecode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $jsonObject = json_decode($json, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException();
        }

        return $jsonObject;
    }

    public static function log($log)
    {
        if ($log instanceof \Exception) {
            $logMsg = sprintf("Error (code: %d, message: %s, type: %s)", $log->getCode(), $log->getMessage(),
                get_class($log));
        } elseif (is_array($log) || is_object($log)) {
            $logMsg = print_r($log, true);
        } else {
            $logMsg = $log;
        }

        error_log($logMsg);
    }
}
