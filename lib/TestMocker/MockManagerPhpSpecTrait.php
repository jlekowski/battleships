<?php

namespace TestMocker;

trait MockManagerPhpSpecTrait
{
    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, array $args)
    {
//        echo PHP_EOL.  debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];
        $mockCallManager = MockCallManager::getInstance();
        if (method_exists($mockCallManager, $name)) {
            $mockResponse = call_user_func_array([$mockCallManager, $name], $args);
            if ($mockResponse instanceof MockCallManager) {
                return $mockResponse;
            }
            $this->mockResponse = $mockResponse;

            return $this->mockResponse;
        }

        return parent::__call($name, $args);
    }
}