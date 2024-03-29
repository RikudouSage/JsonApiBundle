<?php

namespace Rikudou\JsonApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function __construct(private bool $isDebug)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('rikudou_json_api_bundle');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('api_prefix')
                    ->info('The route prefix used with default routing file')
                    ->defaultValue('/api')
                ->end()
                ->booleanNode('clear_cache_hook')
                    ->info('Whether the bundle should hook into cache clearing process to clear the api cache')
                    ->defaultTrue()
                ->end()
                ->booleanNode('property_cache_enabled')
                    ->info('Whether to cache api properties settings, recommended in production environment')
                    ->defaultValue(!$this->isDebug)
                ->end()
                ->booleanNode('auto_discover_resources')
                    ->info('Whether the bundle should look for all classes marked with ApiResource attribute and enable them automatically')
                    ->defaultTrue()
                ->end()
                ->arrayNode('auto_discover_paths')
                    ->info('The paths to scan for auto discovered endpoints')
                    ->scalarPrototype()->end()
                ->end()
                ->booleanNode('disable_autoconfiguration')
                    ->info('Disables automatic injection of resources that implement Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface')
                    ->defaultFalse()
                ->end()
                ->arrayNode('enabled_resources')
                    ->info('A list of classes that will be imported. Useful in combination with disable_autoconfiguration')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name_resolution_service')
                    ->info('The service that will be used to transform class names into resource names and property names into attribute names')
                    ->defaultValue('rikudou_api.name_resolution.camel_case')
                ->end()
                ->booleanNode('pagination')
                    ->info('Set to true to enable pagination of resources')
                    ->defaultTrue()
                ->end()
                ->scalarNode('per_page_limit')
                    ->info('The limit of items per page used if no other limit was specified')
                    ->defaultValue(30)
                ->end()
                ->booleanNode('transform_datetime_objects')
                    ->info('Set to true to automatically transfer DateTimeInterface objects to a string')
                    ->defaultTrue()
                ->end()
                ->scalarNode('datetime_format')
                    ->info('The date time format used if transform_datetime_objects is enabled')
                    ->defaultValue('c')
                ->end()
                ->booleanNode('handle_special_exceptions')
                    ->info('Whether exceptions with special semantic meaning (like JsonApiErrorException) should by handled by the bundle. Should be enabled on production.')
                    ->defaultValue(!$this->isDebug)
                ->end()
                ->scalarNode('cache_adapter')
                    ->info('The cache adapter to use for caching properties etc.')
                    ->defaultValue('cache.app')
                ->end()
                ->booleanNode('allow_resource_overwrite')
                    ->info('Whether to allow overwriting of resources. This could happen if more than one controller for a resource with the same name exists.')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
