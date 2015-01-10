<?php

namespace Battleships;

/**
 * Database config class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6.1
 *
 */
class DBConfig
{
    /**
     * @var string
     */
    protected $dsn;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $host;

    /**
     * Constructor
     * @throws \UnexpectedValueException
     */
    public function __construct()
    {
        switch (DB_TYPE) {
            case "SQLITE":
                $this->dsn = "sqlite:" . SQLITE_PATH . SQLITE_FILE;
                break;

            case "MYSQL":
                $this->dsn = "mysql:dbname=" . MYSQL_DB . ";host=" . MYSQL_HOST;
                $this->username = MYSQL_USER;
                $this->password = MYSQL_PASS;
                break;

            default:
                throw new \UnexpectedValueException(sprintf("Incorrect DB type defined: %s", DB_TYPE));
        }
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
}
