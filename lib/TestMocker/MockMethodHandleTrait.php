<?php

namespace TestMocker;

trait MockMethodHandleTrait
{
    /**
     * Call parent method or log method call and return set value
     * @param string $method
     * @param array $args
     * @return mixed
     */
    private function handleMethod($method, array $args)
    {
//        var_dump("handle: $method");
        $mockCallManager = \TestMocker\MockCallManager::getInstance();
        if (!$mockCallManager->isMocked($method)) {
            return call_user_func_array('parent::' . $method, $args);
        }

        $mockCallManager->getResponse($method, $args);
    }
}
