<?php

namespace TestMocker;

trait MockMethodsTrait
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
     * Call parent method or log method call and return set value
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    private function handleMethod($method, array $args)
    {
        $hasReturnValue = array_key_exists($method, $this->disabledMethods);
        // if in_array() with no type comparison, objects may throw Exception on __toString()
        if (!in_array($method, $this->disabledMethods, true) && !$hasReturnValue) {
            return call_user_func_array('parent::' . $method, $args);
        }

        $this->calledMethods[$method][] = $args;
        if ($hasReturnValue) {
            return $this->disabledMethods[$method];
        }
    }
}