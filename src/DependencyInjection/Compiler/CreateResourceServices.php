<?php

namespace Rikudou\JsonApiBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Rikudou\JsonApiBundle\Annotation\ApiResource;
use Rikudou\JsonApiBundle\Controller\DefaultEntityApiController;
use Rikudou\JsonApiBundle\Interfaces\ApiResourceInterface;
use Rikudou\ReflectionFile;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CreateResourceServices implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('rikudou_api.auto_discover_resources')) {
            return;
        }
        /** @var string $directory */
        $directory = $container->getParameter('kernel.project_dir');

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

            $class = $reflection->getClass();

            if (
                $class->implementsInterface(ApiResourceInterface::class)
                || $annotationReader->getClassAnnotation($class, ApiResource::class)
            ) {
                $definition = new Definition(DefaultEntityApiController::class);
                $definition->addMethodCall('setClassName', [$class->getName()]);
                $definition->addTag('rikudou_api.api_controller');
                $container->setDefinition("rikudou.api_controller.default.{$class->getName()}", $definition);
            }
        }
    }
}
