<?php

namespace Rikudou\JsonApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('rikudou_json_api_bundle');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('auto_discover_paths')
                    ->scalarPrototype()
                        ->addDefaultChildrenIfNoneSet(['%kernel.project_dir%'])
                        ->info('The paths to scan for auto discovered endpoints')
                    ->end()
                ->end()
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
                    ->info('Whether the bundle should look for all classes marked with ApiResource annotation and enable them automatically')
                    ->defaultTrue()
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
            ->end();

        return $treeBuilder;
    }
}
