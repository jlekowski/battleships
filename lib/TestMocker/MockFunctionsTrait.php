<?php
/**
 * @todo permanently disabled functions and methods should be overwritable during tests
 */

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
     */
    public static function disableFunction($function, $returnValue = null)
    {
        $reflectionFunction = new \ReflectionFunction($function);
        self::$disabledFunctions[$reflectionFunction->getShortName()] = $returnValue;
    }

    public function disable($function, $returnValue = null)
    {
        $reflectionFunction = new \ReflectionFunction($function);
        $r = new \ReflectionClass($this);
        if (function_exists(sprintf('\%s\%s', str_replace('spec\\', '', $r->getNamespaceName()), $reflectionFunction->getShortName()))) {

        }

        self::$disabledFunctions[$reflectionFunction->getShortName()] = $returnValue;
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
        $reflectionFunction = new \ReflectionFunction($function);
        $functionName = $reflectionFunction->getShortName();
        if (!array_key_exists($functionName, self::$calledFunctions)) {
            return null;
        }

        return is_null($index) ? self::$calledFunctions[$functionName] : self::$calledFunctions[$functionName][$index];
    }

    /**
     * Call PHP function or log function call and return set value
     * @param string $function
     * @param array $args
     * @return mixed
     */
    public static function getFunctionResponse($function, array $args)
    {
        $reflectionFunction = new \ReflectionFunction($function);
        $functionName = $reflectionFunction->getShortName();
        if (!array_key_exists($functionName, self::$disabledFunctions)) {
            return call_user_func_array($functionName, $args);
        }

        self::$calledFunctions[$functionName][] = $args;

        return self::$disabledFunctions[$functionName];
    }
}
