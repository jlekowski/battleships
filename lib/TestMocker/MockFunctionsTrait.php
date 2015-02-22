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

//    /**
//     * @param $name
//     * @param array $arguments
//     */
//    public function __call($name, array $arguments)
//    {
//        $traitStaticMethods = ['disableFunction', 'getFunctionCalls', 'cleanDisabledFunctions', 'getFunctionResponse'];
//        if (in_array($name, $traitStaticMethods)) {
//
//        }
//
//
////        method_exists($this, $name);
////        $method = new \ReflectionMethod($this, $name);
////        if ($method->isStatic()) return true;
//    }

    /**
     * @param string $function
     * @param mixed $returnValue
     */
    public static function disableFunction($function, $returnValue = null)
    {
        var_dump($function, function_exists($function));
        if (!function_exists($function)) {
            // create function
        }

        self::$disabledFunctions[$function] = $returnValue;
    }

    /**
     * Clean disabled functions
     */
    public static function cleanDisabledFunctions()
    {
        self::$disabledFunctions = [];
    }

    /**
     * @param string $function
     * @param int $index
     * @return mixed
     */
    public static function getFunctionCalls($function, $index = null)
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
            $reflectionFunction = new \ReflectionFunction($function);
            return call_user_func_array($reflectionFunction->getShortName(), $args);
        }

        self::$calledFunctions[$function][] = $args;

        return self::$disabledFunctions[$function];
    }
}
