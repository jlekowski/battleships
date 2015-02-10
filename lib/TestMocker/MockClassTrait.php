<?php

namespace TestMocker;

trait MockClassTrait
{
    /**
     * Initiate mocking tested class
     * @param array $disabledMethods
     * @param array $disabledFunctions
     */
    public function initMock(array $disabledMethods = [], array $disabledFunctions = [])
    {
        (new MockClassCreator())
            ->setDisabledMethods($disabledMethods)
            ->setDisabledFunctions($disabledFunctions)
            ->createMockClassOfSpec($this);
    }
}