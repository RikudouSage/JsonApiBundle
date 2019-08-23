<?php

namespace Rikudou\JsonApiBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class RikudouJsonApiExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $isDebug = $container->getParameter('kernel.debug');
        $configs = $this->processConfiguration(new Configuration($isDebug), $configs);

        $container->setParameter('rikudou_api.api_prefix', $configs['api_prefix']);
        $container->setParameter('rikudou_api.clear_cache_hook', $configs['clear_cache_hook']);
        $container->setParameter('rikudou_api.property_cache_enabled', $configs['property_cache_enabled']);
        $container->setParameter('rikudou_api.auto_discover_resources', $configs['auto_discover_resources']);
        $container->setParameter('rikudou_api.auto_discover_cache_enabled', $configs['auto_discover_cache_enabled']);
        $container->setParameter('rikudou_api.name_resolution_service', $configs['name_resolution_service']);
        $container->setParameter('rikudou_api.pagination', $configs['pagination']);
        $container->setParameter('rikudou_api.per_page_limit', $configs['per_page_limit']);
        $container->setParameter('rikudou_api.transform_datetime_objects', $configs['transform_datetime_objects']);
        $container->setParameter('rikudou_api.datetime_format', $configs['datetime_format']);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $isDebug = $container->getParameter('kernel.debug');
        $configs = $this->processConfiguration(
            new Configuration($isDebug),
            $container->getExtensionConfig($this->getAlias())
        );

        $this->prependCache($configs, $container);
    }

    private function prependCache(array $configs, ContainerBuilder $container)
    {
        $cacheConfig = Yaml::parseFile(__DIR__ . '/../../config/cache.yaml');
        $container->prependExtensionConfig('framework', $cacheConfig['framework']);
    }
}
