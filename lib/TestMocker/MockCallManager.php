<?php

namespace TestMocker;

class MockCallManager
{
    /**
     * @var MockCallManager
     */
    private static $instance;
    /**
     * @var MockCreator
     */
    private $mockCreator;
    /**
     * @var array
     */
    private $mockedCallables = [];
    /**
     * @var array
     */
    private $calledMocks = [];

    /**
     * Singleton - disable controller
     */
    protected function __construct()
    {
        $this->mockCreator = new MockCreator();
    }

    /**
     * @param bool $new
     * @return MockCallManager
     */
    public static function getInstance($new = false)
    {
        if ($new || !self::$instance) {
            self::$instance = new self();
        }

//        print_r(self::$instance->mockedCallables);

        return self::$instance;
    }

    /**
     * @param string $method
     * @param mixed $returnValue
     * @return $this
     */
    public function mockMethod($method, $returnValue = null)
    {
        $this->mockedCallables[$method] = $returnValue;

        return $this;
    }

    /**
     * @param string $function
     * @param string $namespace
     * @param mixed $returnValue
     * @return $this
     */
    public function mockFunction($function, $namespace, $returnValue = null)
    {
//        echo "\nmockFunction\n";
        $this->mockCreator->createFunction($function, $namespace);

        return $this->mockMethod(sprintf('%s\%s', $namespace, $function), $returnValue);
    }

    /**
     * @return $this
     */
    public function cleanMocked()
    {
        $this->mockedCallables = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanMockCalls()
    {
        $this->calledMocks = [];

        return $this;
    }

    /**
     * @param string $callable
     * @param int $index
     * @return mixed
     */
    public function getCalls($callable, $index = null)
    {
        if (!array_key_exists($callable, $this->calledMocks)) {
            return null;
        }

        return is_null($index) ? $this->calledMocks[$callable] : $this->calledMocks[$callable][$index];
    }

    /**
     * @param string $callableName
     * @return bool
     */
    public function isMocked($callableName)
    {
        return array_key_exists($callableName, $this->mockedCallables);
    }

    /**
     * @param string $callable
     * @param array $args
     * @return mixed
     */
    public function getResponse($callable, array $args)
    {
        $this->calledMocks[$callable][] = $args;

        return $this->mockedCallables[$callable];
    }

    public function getFunctionResponse($function, array $args)
    {
        if (array_key_exists($function, $this->mockedCallables)) {
            return $this->getResponse($function, $args);
        }

        // @todo: maybe just get last part after \
        $reflectionFunction = new \ReflectionFunction($function);

        return call_user_func_array($reflectionFunction->getShortName(), $args);
    }
}
