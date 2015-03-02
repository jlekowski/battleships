<?php

namespace TestMocker\PhpSpecExtension;

use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\Maintainer\MaintainerInterface;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\SpecificationInterface;
use TestMocker\MockCallManager;
use TestMocker\MockCreator;

class ExtensionMaintainerListener implements MaintainerInterface
{
    /**
     * @param ExampleNode $example
     *
     * @return boolean
     */
    public function supports(ExampleNode $example)
    {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($example));
        return $example->getSpecification()->getClassReflection()->hasMethod('setMockCallManager');
    }

    /**
     * @param ExampleNode $example
     * @param SpecificationInterface $context
     * @param MatcherManager $matchers
     * @param CollaboratorManager $collaborators
     */
    public function prepare(
        ExampleNode $example,
        SpecificationInterface $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators
    ) {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($example));
//        $r = $example;
//        echo "<pre>";
//        print_r($r);
//        exit;

        $testClassName = $example->getSpecification()->getTitle();
        $mockCreator = new MockCreator();
        $mockCreator->createClass($testClassName, 'spec');

        $context->setMockCallManager(MockCallManager::getInstance(true));
        $context->beAnInstanceOf($mockCreator->getMockClassName());
//        $reflection = $example->getSpecification()->getClassReflection()->getMethod('beAnInstanceOf');
//        $reflection->invokeArgs($context, [$mockClassName]);
    }

    /**
     * @param ExampleNode $example
     * @param SpecificationInterface $context
     * @param MatcherManager $matchers
     * @param CollaboratorManager $collaborators
     */
    public function teardown(
        ExampleNode $example,
        SpecificationInterface $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators
    ) {
        // TODO: Implement teardown() method.
    }

    /**
     * @return integer
     */
    public function getPriority()
    {
        // before let and letgo
        return 11;
    }
}
