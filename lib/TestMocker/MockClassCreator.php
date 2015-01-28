<?php

namespace TestMocker;

use PhpSpec\ObjectBehavior;

class MockClassCreator
{
    /**
     * @var array
     */
    private $disabledMethods = [];

    /**
     * @param ObjectBehavior $spec
     */
    public function createMockClassOfSpec(ObjectBehavior $spec)
    {
        // Spec classes live in 'spec' namespace and are name after suffixing original class name with 'Spec'
        // e.g. spec\MyNamespace\MyClassSpec -> MyNamespace\MyClass
        $specedClass = preg_replace('/(^spec\\\)|(Spec$)/', '', get_class($spec));
        $this->createMockClass($specedClass, 'spec');
    }

    /**
     * @param string $className
     * @param string $topLevelNamespace
     */
    public function createMockClass($className, $topLevelNamespace)
    {
        $reflectionClass = new \ReflectionClass($className);

        $newNamespace = $this->getNamespace($reflectionClass, $topLevelNamespace);
        $shortClassName = $reflectionClass->getShortName();
        // cannot redeclare existing class
        if (class_exists(sprintf('%s\%s', $newNamespace, $shortClassName))) {
            return;
        }

        $classCode = sprintf(
            'namespace %s;

            class %s extends \%s
            {
                use \TestMocker\AccessProtectedTrait, \TestMocker\MockMethodsTrait;

                %s
            }',
            $newNamespace,
            $shortClassName,
            $reflectionClass->getName(),
            $this->getMethodDeclarationsFormatted($reflectionClass)
        );
        eval($classCode);
    }

    /**
     * @param array $disabledMethods
     * @return $this
     */
    public function setDisabledMethods(array $disabledMethods)
    {
        $this->disabledMethods = $disabledMethods;

        return $this;
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
                $this->getMethodParametersFormatted($method),
                $this->getMethodCode($method)
            );
        }

        return implode(PHP_EOL, $methodDeclarations);
    }

    /**
     * @param \ReflectionMethod $method
     * @return string
     */
    private function getMethodParametersFormatted(\ReflectionMethod $method)
    {
        $parameters = [];
        /** @var \ReflectionParameter $parameter */
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = sprintf(
                '%s $%s %s',
                $this->getParameterTypeHintFormatted($parameter),
                $parameter->getName(),
                $this->getParameterDefaultValueFormatted($parameter)
            );
        }

        return implode(', ', $parameters);
    }

    /**
     * @param \ReflectionMethod $method
     * @return string
     */
    private function getMethodCode(\ReflectionMethod $method)
    {
        $disableCode = in_array($method->getName(), $this->disabledMethods)
            ? '$this->disableMethod(__FUNCTION__); '
            : '';

        return $disableCode . 'return $this->handleMethod(__FUNCTION__, func_get_args());';
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     */
    private function getParameterTypeHintFormatted(\ReflectionParameter $parameter)
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
    private function getParameterDefaultValueFormatted(\ReflectionParameter $parameter)
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
