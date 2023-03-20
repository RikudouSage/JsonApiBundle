<?php

namespace Rikudou\JsonApiBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class RikudouJsonApiExtension extends Extension
{
    /**
     * @param array<mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $isDebug = $container->getParameter('kernel.debug');
        assert(is_bool($isDebug));

        return new Configuration($isDebug);
    }

    /**
     * @param array<mixed> $configs
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $isDebug = $container->getParameter('kernel.debug');
        assert(is_bool($isDebug));
        $configs = $this->processConfiguration(new Configuration($isDebug), $configs);

        $container->setAlias('cache.api_annotations', $configs['cache_adapter']);

        $container->setParameter('rikudou_api.api_prefix', $configs['api_prefix']);
        $container->setParameter('rikudou_api.clear_cache_hook', $configs['clear_cache_hook']);
        $container->setParameter('rikudou_api.property_cache_enabled', $configs['property_cache_enabled']);
        $container->setParameter('rikudou_api.auto_discover_resources', $configs['auto_discover_resources']);
        $container->setParameter('rikudou_api.name_resolution_service', $configs['name_resolution_service']);
        $container->setParameter('rikudou_api.pagination', $configs['pagination']);
        $container->setParameter('rikudou_api.per_page_limit', $configs['per_page_limit']);
        $container->setParameter('rikudou_api.transform_datetime_objects', $configs['transform_datetime_objects']);
        $container->setParameter('rikudou_api.datetime_format', $configs['datetime_format']);
        $container->setParameter('rikudou_api.handle_special_exceptions', $configs['handle_special_exceptions']);
        $container->setParameter('rikudou_api.auto_discover_paths', $configs['auto_discover_paths']);
        $container->setParameter('rikudou_api.disable_autoconfiguration', $configs['disable_autoconfiguration']);
        $container->setParameter('rikudou_api.enabled_resources', $configs['enabled_resources']);
        $container->setParameter('rikudou_api.allow_resource_overwrite', $configs['allow_resource_overwrite']);
    }
}
