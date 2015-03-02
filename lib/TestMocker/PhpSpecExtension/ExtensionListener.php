<?php

namespace TestMocker\PhpSpecExtension;

use PhpSpec\Event\ExampleEvent;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Event\SuiteEvent;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\Maintainer\MaintainerInterface;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\SpecificationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TestMocker\MockCallManager;
use TestMocker\MockCreator;

class ExtensionListener implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'beforeExample' => array('beforeExample', -10),
            'afterExample'  => array('afterExample', -10),
            'beforeSuite'   => array('beforeSuite', -10),
            'afterSuite'    => array('afterSuite', -10),
            'beforeSpecification' => ['beforeSpecification'],
        );
    }

    public function beforeSpecification(SpecificationEvent $event)
    {
        // recreate singleton
//        MockCallManager::getInstance(true);
        $spec = $event->getSpecification();
        $r = $spec->getClassReflection();
//        echo "<pre>";
//        print_r($r);
//        exit;
        // Spec classes live in 'spec' namespace and are named by suffixing original class name with 'Spec'
        // e.g. spec\MyNamespace\MyClassSpec -> MyNamespace\MyClass
//        $mockedClass = preg_replace('/(^spec\\\)|(Spec$)/', '', get_class($spec));
        $testClassName = $event->getSpecification()->getTitle();
//        $mockCreator = (new MockCreator())->createClass($testClassName, 'spec');
//        $event;
//        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }

    public function beforeSuite(SuiteEvent $event)
    {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function beforeExample(ExampleEvent $event)
    {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function afterExample(ExampleEvent $event)
    {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function afterSuite(SuiteEvent $event)
    {
//        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
}
