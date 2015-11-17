<?php

namespace Battleships;

use Battleships\DBConfig;
use Battleships\Exception\DBException;

/**
 * Database class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.2
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class DB extends \PDO
{
    /**
     * Constructor
     * @param DBConfig $dbConfig
     */
    public function __construct(DBConfig $dbConfig)
    {
        parent::__construct($dbConfig->getDsn(), $dbConfig->getHost(), $dbConfig->getUsername(), $dbConfig->getPassword());
    }

    /**
     * Executes query with array values (for IN clause)
     *
     * Allows to set array values for IN clause, e.g. WHERE id = :id | array(':id' => array(1,5,6))
     *
     * @param string $query SQL Query prepare
     * @param array $parameters Values for SQL set in PDO prepare
     * @return array SQL Query result
     * @throws \Battleships\Exception\DBException
     */
    public function getAll($query, array $parameters = array())
    {
        // if array is multidimensional, i.e. an array is used for IN clause -
        // otherwise simple prepare and execute in one method
        if (count($parameters) != count($parameters, COUNT_RECURSIVE)) {
            foreach ($parameters as $key => $value) {
                // values must be reorganised only for array values
                if (!is_array($value)) {
                    continue;
                }
                // for array values named marker must be used, i.e. :id, :event, ...
                if (is_integer($key)) {
                    throw new DBException("DB query error - array PDO values must be set in assiociative array");
                }

                $markers = array();
                foreach ($value as $k => $v) {
                    $markers[] = $key.$k; // e.g. ':id0', ':id1', ...
                    $parameters[ $key.$k ] = $v;
                }

                $query = str_replace($key, implode(",", $markers), $query);
                unset($parameters[$key]); // to replace :id with :id0, :id1, ...
            }
        }

        $sth = $this->prepare($query, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));

        if ($sth === false) {
            throw new DBException("Statement could not be prepared");
        }

        if ($sth->execute($parameters) === false) {
            throw new DBException("Statement could not be executed");
        }

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Runs SQL Query and returns the first row of the result (query or fQuery when parameters provided)
     *
     * @param string $query SQL Query (or SQL Query prepare)
     * @param array $parameters Values for SQL set in PDO prepare
     *
     * @return array First row of the SQL Query result
     */
    public function getFirst($query, array $parameters = array())
    {
        $result = $this->getAll($query, $parameters);

        return empty($result) ? $result : current($result);
    }
}
