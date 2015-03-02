<?php

namespace TestMocker;

use PhpSpec\ObjectBehavior;

class MockCreator
{
    /**
     * @var string
     */
    protected $mockClassName;

    /**
     * @param ObjectBehavior $spec
     */
    public function createClassOfSpec(ObjectBehavior $spec)
    {
        // Spec classes live in 'spec' namespace and are named by suffixing original class name with 'Spec'
        // e.g. spec\MyNamespace\MyClassSpec -> MyNamespace\MyClass
        $mockedClass = preg_replace('/(^spec\\\)|(Spec$)/', '', get_class($spec));
        $this->createClass($mockedClass, 'spec');
    }

    /**
     * @param string $className
     * @param string $topLevelNamespace
     */
    public function createClass($className, $topLevelNamespace)
    {
        $reflectionClass = new \ReflectionClass($className);

        $newNamespace = $this->getNamespace($reflectionClass, $topLevelNamespace);
        $shortClassName = $reflectionClass->getShortName();
        $this->mockClassName = sprintf('%s\%s', $newNamespace, $shortClassName);
        // cannot redeclare existing class
        if (class_exists($this->mockClassName)) {
            return;
        }

        $classCode = sprintf('
            namespace %s
            {
                class %s extends \%s
                {
                    use \TestMocker\AccessProtectedTrait, \TestMocker\MockMethodHandleTrait;

                    %s
                }
            }',
            $newNamespace,
            $shortClassName,
            $reflectionClass->getName(),
            $this->getMethodDeclarationsFormatted($reflectionClass)
        );
//        echo "\n$classCode\n";
        eval($classCode);
    }

    /**
     * @param string $function
     * @param string $namespace
     * @throws \Exception
     */
    public function createFunction($function, $namespace)
    {
        if (function_exists(sprintf('\%s\%s', $namespace, $function))) {
            return;
        }

        $functionCode = sprintf('
            namespace %s
            {
                %s
            }',
            $namespace,
            $this->getFunctionDeclarationsFormatted($function)
        );

//        echo "\n$functionCode\n";
        eval($functionCode);
    }

    /**
     * @return string
     */
    public function getMockClassName()
    {
        return $this->mockClassName;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $topLevelNamespace
     * @return string
     */
    private function getNamespace(\ReflectionClass $reflectionClass, $topLevelNamespace)
    {
        return $reflectionClass->getNamespaceName()
            ? sprintf('%s\%s', $topLevelNamespace, $reflectionClass->getNamespaceName())
            : $topLevelNamespace;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return string
     */
    private function getMethodDeclarationsFormatted(\ReflectionClass $reflectionClass)
    {
        $methodDeclarations = [];
        /** @var \ReflectionMethod $method */
        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isFinal()) {
                continue;
            }

            $methodDeclarations[] = sprintf(
                'public %s function %s(%s) { %s }',
                ($method->isStatic() ? 'static' : ''),
                $method->getName(),
                $this->getFunctionParametersFormatted($method, true),
                $this->getMethodCode($method)
            );
        }

        return implode(PHP_EOL, $methodDeclarations);
    }

    /**
     * @param string $function
     * @return string
     * @throws \Exception
     */
    private function getFunctionDeclarationsFormatted($function)
    {
        if (!function_exists($function)) {
            throw new \Exception(sprintf('Function %s does not exist', $function));
        }

        $reflectionFunction = new \ReflectionFunction($function);
        $functionDeclaration = sprintf('
            function %s(%s) {
                return \TestMocker\MockCallManager::getInstance()->getFunctionResponse(__FUNCTION__, func_get_args());
            }',
            $reflectionFunction->getName(),
            $this->getFunctionParametersFormatted($reflectionFunction, true)
        );

        return $functionDeclaration;
    }

    /**
     * @param \ReflectionFunctionAbstract $function
     * @param bool $forDeclaration
     * @return string
     */
    protected function getFunctionParametersFormatted(\ReflectionFunctionAbstract $function, $forDeclaration)
    {
        $parameters = [];
        /** @var \ReflectionParameter $parameter */
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $forDeclaration
                ? $this->getParameterDeclarationFormatted($parameter)
                : $this->getParameterPassFormatted($parameter);
        }

        return implode(', ', $parameters);
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterDeclarationFormatted(\ReflectionParameter $parameter)
    {
        return sprintf(
            '%s $%s %s',
            $this->getParameterTypeHintFormatted($parameter),
            $parameter->getName(),
            $this->getParameterDefaultValueFormatted($parameter)
        );
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterPassFormatted(\ReflectionParameter $parameter)
    {
        return sprintf(
            '%s$%s',
            ($parameter->isPassedByReference() ? '&' : ''),
            $parameter->getName()
        );
    }

    /**
     * @param \ReflectionMethod $method
     * @return string
     */
    protected function getMethodCode(\ReflectionMethod $method)
    {
        return sprintf(
            'return $this->handleMethod(__FUNCTION__, [%s]);',
            $this->getFunctionParametersFormatted($method, false)
        );
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterTypeHintFormatted(\ReflectionParameter $parameter)
    {
        if ($parameterClass = $parameter->getClass()) {
            $typeHint = '\\' . $parameterClass->getName();
        } elseif ($parameter->isArray()) {
            $typeHint = 'array';
        } elseif ($parameter->isCallable()) {
            $typeHint = 'callable';
        } else {
            $typeHint = '';
        }

        return $typeHint;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterDefaultValueFormatted(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            $defaultValue = '= ' . var_export($parameter->getDefaultValue(), true);
        } elseif ($parameter->isOptional()) {
            $defaultValue = '= null';
        } else {
            $defaultValue = '';
        }

        return $defaultValue;
    }
}
