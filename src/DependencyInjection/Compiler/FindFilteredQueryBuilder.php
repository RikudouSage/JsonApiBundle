<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Rikudou\JsonApiBundle\Service\Filter\DefaultFilteredQueryBuilder;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FindFilteredQueryBuilder implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $builders = $container->findTaggedServiceIds('rikudou_api.filtered_query_builder');
        $resultingBuilder = null;

        foreach ($builders as $service => $tags) {
            $definition = $container->getDefinition($service);
            if ($definition->getClass() === DefaultFilteredQueryBuilder::class) {
                continue;
            }
            if ($definition->isAbstract()) {
                continue;
            }
            $resultingBuilder = $service;
            break;
        }

        if ($resultingBuilder === null) {
            $resultingBuilder = 'rikudou_api.query_builder.default';
        }

        $container->setAlias(FilteredQueryBuilderInterface::class, (string) $resultingBuilder);
    }
}
