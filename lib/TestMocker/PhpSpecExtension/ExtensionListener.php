<?php

namespace TestMocker\PhpSpecExtension;

use PhpSpec\Event\ExampleEvent;
use PhpSpec\Event\SuiteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        );
    }

    public function beforeSuite(SuiteEvent $event)
    {
        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function beforeExample(ExampleEvent $event)
    {
        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function afterExample(ExampleEvent $event)
    {
        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
    public function afterSuite(SuiteEvent $event)
    {
        printf("\n%s: %s\n", __FUNCTION__, get_class($event));
    }
}