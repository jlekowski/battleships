<?php

namespace TestMocker;

use PhpSpec\ObjectBehavior;

class MockClassCreator
{
    /**
     * @var array
     */
    protected $permanentlyDisabledMethods = [];

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
        $this->permanentlyDisabledMethods = $disabledMethods;

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
                $this->getMethodParametersFormatted($method, true),
                $this->getMethodCode($method)
            );
        }

        return implode(PHP_EOL, $methodDeclarations);
    }

    /**
     * @param \ReflectionMethod $method
     * @param bool $forDeclaration
     * @return string
     */
    protected function getMethodParametersFormatted(\ReflectionMethod $method, $forDeclaration)
    {
        $parameters = [];
        /** @var \ReflectionParameter $parameter */
        foreach ($method->getParameters() as $parameter) {
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
            '%s return $this->handleMethod(__FUNCTION__, [%s]);',
            (in_array($method->getName(), $this->permanentlyDisabledMethods) ? '$this->disableMethod(__FUNCTION__);' : ''),
            $this->getMethodParametersFormatted($method, false)
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
