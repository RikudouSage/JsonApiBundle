<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PopulateResourceLocator implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds('rikudou_api.api_controller');
        $locator = $container->getDefinition('rikudou_api.api_resource_locator');

        foreach ($services as $service => $tags) {
            $definition = $container->getDefinition($service);
            if ($definition->isAbstract()) {
                continue;
            }
            $locator->addMethodCall('addController', [new Reference($service)]);
        }
    }
}
