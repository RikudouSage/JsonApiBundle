<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Error;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use Rikudou\JsonApiBundle\Annotation\ApiResource;
use Rikudou\JsonApiBundle\Controller\DefaultEntityApiController;
use Rikudou\JsonApiBundle\Interfaces\ApiResourceInterface;
use Rikudou\ReflectionFile;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;

final class CreateAutomaticEntityControllers implements CompilerPassInterface
{
    private const IGNORED_CLASSES = [
        AnnotationClassLoader::class,
    ];

    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('rikudou_api.auto_discover_resources')) {
            return;
        }
        /** @var array $directories */
        $directories = $container->getParameter('rikudou_api.auto_discover_paths');
        if (!is_countable($directories) || !count($directories)) {
            $directories = [$container->getParameter('kernel.project_dir')];
        }

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory)
            );

            $annotationReader = new AnnotationReader();

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                if (!$path = $file->getRealPath()) {
                    continue;
                }

                $reflection = new ReflectionFile($path);
                if (!$reflection->containsClass()) {
                    continue;
                }

                try {
                    $class = $reflection->getClass();
                } catch (ReflectionException | Error | RuntimeException $e) {
                    continue;
                }

                if (in_array($class->getName(), self::IGNORED_CLASSES)) {
                    continue;
                }

                if (
                    $class->implementsInterface(ApiResourceInterface::class)
                    || $annotationReader->getClassAnnotation($class, ApiResource::class)
                ) {
                    $definition = new Definition();
                    $definition->setClass(DefaultEntityApiController::class);
                    $definition->addMethodCall('setClassName', [$class->getName()]);
                    $definition->addMethodCall('setContainer', [new Reference('service_container')]);
                    $definition->addTag('rikudou_api.api_controller');
                    $definition->addTag('controller.service_arguments');
                    $definition->addTag('container.service_subscriber');
                    $definition->setPublic(true);

                    $container->setDefinition("rikudou.api_controller.default.{$class->getName()}", $definition);
                }
            }
        }
    }
}
