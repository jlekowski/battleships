<?php
namespace Battleships;

/**
 * Database class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.4
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.3
 *
 */
class DB extends \PDO
{
    /**
     * PDO Statement Object
     *
     * Example: object(PDOStatement)#3 (1) {'queryString' => "SELECT ..."}
     *
     * @var object
     */
    private $sth;

    /**
     * Error variable
     *
     * Example: HY000 | 8 | attempt to write a readonly database
     *
     * @var string
     */
    private $error;

    /**
     * Database type variable
     *
     * Example: SQLITE, MYSQL
     *
     * @var string
     */
    private $dbType;

    /**
     * Initiates PDO Object and creates DB tables if required
     *
     * @param string $dbType Type of the database
     *
     * @return void
     */
    public function __construct($dbType)
    {
        $this->dbType = $dbType;

        try {
            switch ($this->dbType) {
                case "SQLITE":
                    parent::__construct("sqlite:" . SQLITE_PATH . SQLITE_FILE);
                    break;

                case "MYSQL":
                    parent::__construct("mysql:dbname=" . MYSQL_DB . ";host=" . MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
                    break;

                default:
                    throw new \Exception("Correct DB type is missing (" . $dbType .")");
            }
        } catch (\Exception $e) {
            Misc::log($e->getMessage());
            exit('Database error occurred');
        }
    }

    /**
     * Returns Errors
     *
     * @return string Error
     */
    public function getError()
    {
        if ($this->isErrorCode($this->errorCode())) {
            $error = implode(" | ", $this->errorInfo());
        } elseif ($this->sth instanceof \PDOStatement && $this->isErrorCode($this->sth->errorCode())) {
            $error = implode(" | ", $this->sth->errorInfo());
        } else {
            $error = $this->error;
        }

        return $error;
    }

    /**
     * Sets Errors
     *
     * @param string $error Error to be set
     *
     * @return void
     */
    private function setError($error)
    {
        $this->error = $error;
    }

    /**
     * Checks whether the code is an error code, or not
     *
     * @param mixed $code
     *
     * @return bool
     */
    private function isErrorCode($code)
    {
        // "00000" error code means that everything is correct
        return !in_array($code, array(null, "00000"), true);
    }

    /**
     * Returns Database type
     *
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * Runs SQL Query and returns result (all records)
     *
     * @param string $query SQL Query
     *
     * @return array|false SQL Query result
     */
    public function query($query)
    {
        $result = parent::query($query);

        if ($result === false) {
            return false;
        }

        return $result->fetchall(\PDO::FETCH_ASSOC);
    }

    /**
     * Sets query using PDO prepare
     *
     * @param string $query SQL Query prepare
     *
     * @return bool Whether an error in PDO prepare ocurred
     */
    public function prepare($query)
    {
        $this->sth = parent::prepare($query, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));

        if ($this->sth === false) {
            return false;
        }

        return true;
    }

    /**
     * Executes query using PDO execute
     *
     * @param array $values Values for SQL set in PDO prepare
     *
     * @return array|false SQL Query result
     */
    public function execute($values)
    {
        $result = $this->sth->execute($values);

        if ($result === false) {
            return false;
        }

        return $this->sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Executes query with array values (for IN clause)
     *
     * Allows to set array values for IN clause, e.g. WHERE id = :id | array(':id' => array(1,5,6))
     *
     * @param string $query SQL Query prepare
     * @param array $parameters Values for SQL set in PDO prepare
     *
     * @return array|false SQL Query result
     */
    public function fQuery($query, $parameters)
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
                    $this->setError("DB query error - array PDO values must be set in assiociative array");
                    return false;
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

        $this->prepare($query);

        return $this->execute($parameters);
    }

    /**
     * Runs SQL Query and returns the first row of the result (query or fQuery when parameters provided)
     *
     * @param string $query SQL Query (or SQL Query prepare)
     * @param array $parameters Values for SQL set in PDO prepare
     *
     * @return array|false First row of the SQL Query result
     */
    public function getFirst($query, $parameters = array())
    {
        $result = empty($parameters) ? $this->query($query) : $this->fQuery($query, $parameters);

        return empty($result) ? $result : current($result);
    }
}
