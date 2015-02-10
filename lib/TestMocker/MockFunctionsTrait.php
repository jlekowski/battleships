<?php

namespace TestMocker;

trait MockFunctionsTrait
{
    /**
     * @var array
     */
    private static $disabledFunctions = [];
    /**
     * @var array
     */
    private static $calledFunctions = [];

    /**
     * @param string $function
     * @param mixed $returnValue
     * @return $this
     */
    public function disableFunction($function, $returnValue = null)
    {
        self::$disabledFunctions[$function] = $returnValue;

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanDisabledFunctions()
    {
        self::$disabledFunctions = [];

        return $this;
    }

    /**
     * @param string $function
     * @param int $index
     * @return mixed
     */
    public function getFunctionCalls($function, $index = null)
    {
        if (!array_key_exists($function, self::$calledFunctions)) {
            return null;
        }

        return is_null($index) ? self::$calledFunctions[$function] : self::$calledFunctions[$function][$index];
    }

    /**
     * Call PHP function or log function call and return set value
     * @param string $function
     * @param array $args
     * @return mixed
     */
    public static function getFunctionResponse($function, array $args)
    {
        if (!array_key_exists($function, self::$disabledFunctions)) {
            return call_user_func_array($function, $args);
        }

        self::$calledFunctions[$function][] = $args;

        return self::$disabledFunctions[$function];
    }
}
