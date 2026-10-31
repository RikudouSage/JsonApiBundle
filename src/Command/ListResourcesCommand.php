<?php

namespace Rikudou\JsonApiBundle\Command;

use Rikudou\JsonApiBundle\Service\ApiResourceLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'json-api:list-resources', description: 'List all resources')]
final class ListResourcesCommand
{
    public function __construct(
        private readonly ApiResourceLocator $resourceLocator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
    ): int {
        $resourceNames = $this->resourceLocator->getResourceNames(false);

        $table = $io->createTable()->setHeaders([
            'Resource',
            'Resource class',
            'Controller',
        ]);

        foreach ($resourceNames as $resourceName) {
            $table->addRow([
                $resourceName,
                $this->resourceLocator->getEntityFromResourceType($resourceName),
                $this->resourceLocator->findControllerForResource($resourceName)::class,
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}