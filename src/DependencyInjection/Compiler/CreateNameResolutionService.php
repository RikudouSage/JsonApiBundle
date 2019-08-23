<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use function is_a;
use LogicException;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use function sprintf;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CreateNameResolutionService implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceId = $container->getParameter('rikudou_api.name_resolution_service');
        $service = $container->getDefinition($serviceId);
        if (!is_a($service->getClass(), ApiNameResolutionInterface::class, true)) {
            throw new LogicException(
                sprintf(
                    'The name resolution service must implement the %s interface',
                    ApiNameResolutionInterface::class
                )
            );
        }

        $container->setAlias(ApiNameResolutionInterface::class, $serviceId);
    }
}
