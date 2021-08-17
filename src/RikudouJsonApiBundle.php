<?php

namespace Rikudou\JsonApiBundle;

use Rikudou\JsonApiBundle\DependencyInjection\Compiler\CreateAutomaticEntityControllers;
use Rikudou\JsonApiBundle\DependencyInjection\Compiler\CreateNameResolutionService;
use Rikudou\JsonApiBundle\DependencyInjection\Compiler\FindFilteredQueryBuilder;
use Rikudou\JsonApiBundle\DependencyInjection\Compiler\PopulateNormalizerLocator;
use Rikudou\JsonApiBundle\DependencyInjection\Compiler\PopulateResourceLocator;
use Rikudou\JsonApiBundle\DependencyInjection\Compiler\SetEntityControllerDependencies;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer\ApiObjectNormalizerInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RikudouJsonApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(ApiControllerInterface::class)
            ->addTag('rikudou_api.api_controller');
        $container->registerForAutoconfiguration(ApiObjectNormalizerInterface::class)
            ->addTag('rikudou_api.api_normalizer');
        $container->registerForAutoconfiguration(FilteredQueryBuilderInterface::class)
            ->addTag('rikudou_api.filtered_query_builder');

        $container->addCompilerPass(new CreateNameResolutionService());
        $container->addCompilerPass(new PopulateNormalizerLocator());

        $container->addCompilerPass(
            new FindFilteredQueryBuilder(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -1,
        );
        $container->addCompilerPass(
            new CreateAutomaticEntityControllers(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -2,
        );
        $container->addCompilerPass(
            new SetEntityControllerDependencies(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -3,
        );
        $container->addCompilerPass(
            new PopulateResourceLocator(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -4,
        );
    }
}
