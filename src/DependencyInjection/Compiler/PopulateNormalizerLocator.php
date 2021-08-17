<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PopulateNormalizerLocator implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('rikudou_api.api_normalizer');
        $locator = $container->getDefinition('rikudou_api.api_normalizer_locator');

        foreach ($services as $service => $tags) {
            $definition = $container->getDefinition($service);
            if ($definition->isAbstract()) {
                continue;
            }
            $locator->addMethodCall('addNormalizer', [new Reference($service)]);
        }
    }
}
