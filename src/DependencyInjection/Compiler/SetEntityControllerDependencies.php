<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use ReflectionClass;
use ReflectionException;
use Rikudou\JsonApiBundle\Controller\EntityApiController;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SetEntityControllerDependencies implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container)
    {
        $controllers = $container->findTaggedServiceIds('rikudou_api.api_controller');
        foreach ($controllers as $controller => $tags) {
            $definition = $container->getDefinition($controller);
            $reflection = new ReflectionClass($definition->getClass());
            if (
                $definition->isAbstract()
                || $reflection->isAbstract()
                || !is_a($reflection->getName(), EntityApiController::class, true)
            ) {
                continue;
            }

            $definition->addMethodCall(
                'setFilteredQueryBuilder',
                [new Reference(FilteredQueryBuilderInterface::class)]
            );
            $definition->addMethodCall(
                'setRequestStack',
                [new Reference('request_stack')]
            );
            $definition->addMethodCall(
                'setPaginationEnabled',
                [$container->getParameter('rikudou_api.pagination')]
            );
            $definition->addMethodCall(
                'setDefaultPerPageLimit',
                [$container->getParameter('rikudou_api.per_page_limit')]
            );
            $definition->addMethodCall(
                'setObjectParser',
                [new Reference('rikudou_api.object_parser.parser')]
            );
            $definition->addMethodCall(
                'setEventDispatcher',
                [new Reference('event_dispatcher')]
            );
            $definition->addMethodCall(
                'setEntityManager',
                [new Reference('doctrine.orm.entity_manager')]
            );
            $definition->addMethodCall(
                'setUrlGenerator',
                [new Reference('router')]
            );
        }
    }
}
