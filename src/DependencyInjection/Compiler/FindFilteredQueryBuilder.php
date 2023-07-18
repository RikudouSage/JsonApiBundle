<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Rikudou\JsonApiBundle\Service\Filter\DefaultFilteredQueryBuilder;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class FindFilteredQueryBuilder implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
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

        $definition = $container->getDefinition((string) $resultingBuilder);
        $class = $definition->getClass();
        assert(is_string($class));

        if (method_exists($class, 'setObjectValidator')) {
            $definition->addMethodCall(
                'setObjectValidator',
                [new Reference('rikudou_api.object_parser.validator')],
            );
        }

        $definition->addMethodCall(
            'setEntityManager',
            [new Reference('doctrine.orm.entity_manager')],
        );
        $definition->addMethodCall(
            'setPropertyParser',
            [new Reference('rikudou_api.object_parser.property_parser')],
        );

        $container->setAlias(FilteredQueryBuilderInterface::class, (string) $resultingBuilder);
    }
}
