<?php

namespace TestMocker;

trait MockMethodsTrait
{
    /**
     * @var array
     */
    private $disabledMethods = [];
    /**
     * @var array
     */
    private $calledMethods = [];

    /**
     * @param string $method
     * @param mixed $returnValue
     * @return $this
     */
    public function disableMethod($method, $returnValue = null)
    {
        $this->disabledMethods[$method] = $returnValue;

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanDisabledMethods()
    {
        $this->disabledMethods = [];

        return $this;
    }

    /**
     * @param string $method
     * @param int $index
     * @return mixed
     */
    public function getMethodCalls($method, $index = null)
    {
        if (!array_key_exists($method, $this->calledMethods)) {
            return null;
        }

        return is_null($index) ? $this->calledMethods[$method] : $this->calledMethods[$method][$index];
    }

    /**
     * Call parent method or log method call and return set value
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    private function handleMethod($method, array $args)
    {
        if (!array_key_exists($method, $this->disabledMethods)) {
            return call_user_func_array('parent::' . $method, $args);
        }

        $this->calledMethods[$method][] = $args;

        return $this->disabledMethods[$method];
    }
}