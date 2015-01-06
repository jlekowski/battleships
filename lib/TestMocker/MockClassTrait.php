<?php

namespace TestMocker;

use Battleships\Misc;

trait MockClassTrait
{
    /**
     * @var array
     */
    public $disabledMethods = [];
    /**
     * @var array
     */
    public $calledMethods = [];

    /**
     * @var mixed
     */
    private $mockedObject;

    /**
     * @param string $className
     * @param array $arguments
     */
    public function init($className, array $arguments)
    {
        if (!class_exists('MockClass')) {
            eval("class MockClass extends $className { use \\TestMocker\\AccessProtectedTrait; }");
        }
        $reflectionClass = new \ReflectionClass('MockClass');
        $this->mockedObject = $reflectionClass->newInstanceArgs($arguments);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
//        Misc::log([$method, $args]);
        $hasReturnValue = array_key_exists($method, $this->disabledMethods);
        // if in_array() with no type comparison, objects may throw Exception on __toString()
        if (!in_array($method, $this->disabledMethods, true) && !$hasReturnValue) {
            return call_user_func_array(array($this->mockedObject, $method), $args);
        }

        $this->calledMethods[$method][] = $args;
        if ($hasReturnValue) {
            return $this->disabledMethods[$method];
        }
    }
}