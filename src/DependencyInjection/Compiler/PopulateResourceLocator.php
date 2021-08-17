<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PopulateResourceLocator implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('rikudou_api.api_controller');
        $locator = $container->getDefinition('rikudou_api.api_resource_locator');

        if ($container->getParameter('rikudou_api.disable_autoconfiguration')) {
            $allowedClasses = $container->getParameter('rikudou_api.enabled_resources');
        } else {
            $allowedClasses = [];
        }

        foreach ($services as $service => $tags) {
            $definition = $container->getDefinition($service);
            if ($definition->isAbstract()) {
                continue;
            }
            if (count($allowedClasses) && !in_array($definition->getClass(), $allowedClasses, true)) {
                continue;
            }
            $locator->addMethodCall('addController', [new Reference($service), $service]);
        }
    }
}
