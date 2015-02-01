<?php

namespace spec\TestMocker;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockClassTrait;

class MockClassCreatorSpec extends ObjectBehavior
{
    use MockClassTrait;

    public function let()
    {
        $this->initMock();
        $this->beAnInstanceOf('spec\TestMocker\MockClassCreator');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\TestMocker\MockClassCreator');
    }

    public function it_gets_method_parameters(\ReflectionMethod $method)
    {
        // no parameters
        $method->getParameters()->willReturn([]);
        $this->getMethodParametersFormatted($method, true)->shouldReturn('');
    }

    public function it_get_parameter_declaration(\ReflectionParameter $parameter, \ReflectionClass $reflectionClass)
    {
        $parameter->getClass()->willReturn($reflectionClass);
        $reflectionClass->getName()->willReturn('stdClass');
        $parameter->getName()->willReturn('param_name');
        $parameter->isDefaultValueAvailable()->willReturn(false);
        $parameter->isOptional()->willReturn(false);

        $this->getParameterDeclarationFormatted($parameter)->shouldReturn('\stdClass $param_name ');
    }

    public function it_gets_parameter_to_pass(\ReflectionParameter $parameter)
    {
        $parameter->getName()->willReturn('param_name');
        // by value
        $parameter->isPassedByReference()->willReturn(false);
        $this->getParameterPassFormatted($parameter)->shouldReturn('$param_name');
        // by reference
        $parameter->isPassedByReference()->willReturn(true);
        $this->getParameterPassFormatted($parameter)->shouldReturn('&$param_name');
    }

    public function it_gets_method_code(\ReflectionMethod $method)
    {
        $method->getName()->willReturn('method name');
        $method->getParameters()->willReturn([]);
        $this->getMethodCode($method)->shouldReturn(' return $this->handleMethod(__FUNCTION__, []);');

        $this->permanentlyDisabledMethods = ['method name'];
        $this->getMethodCode($method)->shouldReturn(
            '$this->disableMethod(__FUNCTION__); return $this->handleMethod(__FUNCTION__, []);'
        );
    }

    public function it_gets_parameter_type_hint(\ReflectionParameter $parameter, \ReflectionClass $reflectionClass)
    {
        // object
        $parameter->getClass()->willReturn($reflectionClass);
        $reflectionClass->getName()->willReturn('Namespace\ClassName');
        $this->getParameterTypeHintFormatted($parameter)->shouldReturn('\Namespace\ClassName');
        // array
        $parameter->getClass()->willReturn(false);
        $parameter->isArray()->willReturn(true);
        $this->getParameterTypeHintFormatted($parameter)->shouldReturn('array');
        // callable
        $parameter->isArray()->willReturn(false);
        $parameter->isCallable()->willReturn(true);
        $this->getParameterTypeHintFormatted($parameter)->shouldReturn('callable');
        // no type hint
        $parameter->isCallable()->willReturn(false);
        $this->getParameterTypeHintFormatted($parameter)->shouldReturn('');
    }

    public function it_gets_parameter_default_value(\ReflectionParameter $parameter)
    {
        // default array
        $parameter->isDefaultValueAvailable()->willReturn(true);
        $parameter->getDefaultValue()->willReturn([1, 'a' => 2]);
        $this->getParameterDefaultValueFormatted($parameter)->shouldReturn("= array (\n  0 => 1,\n  'a' => 2,\n)");
        // default int
        $parameter->getDefaultValue()->willReturn(1);
        $this->getParameterDefaultValueFormatted($parameter)->shouldReturn("= 1");
        // default string
        $parameter->getDefaultValue()->willReturn('text');
        $this->getParameterDefaultValueFormatted($parameter)->shouldReturn("= 'text'");

        // default null
        $parameter->isDefaultValueAvailable()->willReturn(false);
        $parameter->isOptional()->willReturn(true);
        $this->getParameterDefaultValueFormatted($parameter)->shouldReturn("= null");

        // no default
        $parameter->isOptional()->willReturn(false);
        $this->getParameterDefaultValueFormatted($parameter)->shouldReturn("");
    }
}
