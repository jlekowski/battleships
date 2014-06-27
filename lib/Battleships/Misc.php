<?php
namespace Battleships;

/**
 * General functions
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5
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

    public static function log($log)
    {
        error_log($log);
    }
}
