<?php

namespace TestMocker\PhpSpecExtension;

use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\ServiceContainer;

class Extension implements ExtensionInterface
{
    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
//        echo "\nLOAD: ".get_class($container)."\n";

        $container->setShared('event_dispatcher.listeners.my_extension', function ($container) {
            $listener = new ExtensionListener();
//            $listener->setIO($container->get('console.io'));
//            $listener->setOptions($container->getParam('code_coverage', array()));

            return $listener;
        });

        $container->setShared('runner.maintainers.my_extension', function ($container) {
            $listener = new ExtensionMaintainerListener();

            return $listener;
        });
    }
}
