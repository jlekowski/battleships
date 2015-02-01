<?php

namespace TestMocker;

trait AccessProtectedTrait
{
    /**
     * Access parent protected property
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->$property;
    }

    /**
     * Set parent protected property
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
