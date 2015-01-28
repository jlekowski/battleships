<?php

namespace TestMocker;

trait MockClassTrait
{
    /**
     * Initiate mocking tested class
     * @param array $disabledMethods
     */
    public function initMock(array $disabledMethods = [])
    {
        (new MockClassCreator())->setDisabledMethods($disabledMethods)->createMockClassOfSpec($this);
    }
}