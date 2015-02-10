<?php

namespace TestMocker;

trait MockFunctionsTrait
{
    /**
     * @var array
     */
    private $disabledFunctions = [];
    /**
     * @var array
     */
    private $calledFunctions = [];

    /**
     * @param string $function
     * @param mixed $returnValue
     * @return $this
     */
    public function disableFunction($function, $returnValue = null)
    {
        $this->disabledFunctions[$function] = $returnValue;

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanDisabledFunctions()
    {
        $this->disabledFunctions = [];

        return $this;
    }

    /**
     * @param string $function
     * @param int $index
     * @return mixed
     */
    public function getFunctionCalls($function, $index = null)
    {
        if (!array_key_exists($function, $this->calledFunctions)) {
            return null;
        }

        return is_null($index) ? $this->calledFunctions[$function] : $this->calledFunctions[$function][$index];
    }

    /**
     * Call PHP function or log function call and return set value
     * @param string $function
     * @param array $args
     * @return mixed
     */
    public function getFunctionResponse($function, array $args)
    {
        if (!array_key_exists($function, $this->disabledFunctions)) {
            return call_user_func_array('parent::' . $function, $args);
        }

        $this->calledFunctions[$function][] = $args;

        return $this->disabledFunctions[$function];
    }
}
