<?php

namespace Rikudou\JsonApiBundle\Command;

use ReflectionClass;
use Rikudou\JsonApiBundle\Service\ApiResourceLocator;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectAccessor;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'json-api:mapping', description: 'Get a detailed mapping for a single resource')]
final class GetMappingCommand
{
    public function __construct(
        private readonly ApiPropertyParser $propertyParser,
        private readonly ApiResourceLocator $resourceLocator,
        private readonly ApiObjectParser $apiObjectParser,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Either the class name or resource name')]
        string $resource,
    ): int {
        $object = $this->getObject($resource);
        $properties = $this->propertyParser->getApiProperties($object);

        $io->table(['resource', 'class', 'plural'], [[
            $this->apiObjectParser->getResourceName($object),
            $object::class,
            $this->apiObjectParser->getPluralResourceName($object),
        ]]);

        $table = $io->createTable()->setHeaders([
            'Name',
            'Type',
            'Getter',
            'Setter',
            'Adder',
            'Remover',
            'Is relation?',
            'Readonly?',
            'Silent fail?'
        ]);

        $bool = fn (bool $value): string => $value ? 'Yes' : 'No';
        $nullable = fn (?string $value): string => $value === null ? '-' : $value;
        $triState = fn (?bool $value, string $onTrue, string $onFalse, string $onNull) => $value === null ? $onNull : ($value ? $onTrue : $onFalse);

        foreach ($properties as $name => $property) {
            $table->addRow([
                $name,
                $property->getType() === ApiObjectAccessor::TYPE_PROPERTY ? 'Property' : 'Method',
                $property->getGetter(),
                $nullable($property->getSetter()),
                $nullable($property->getAdder()),
                $nullable($property->getRemover()),
                $triState($property->isRelation(), 'Yes', 'No', 'Auto-detect'),
                $bool($property->isReadonly()),
                $bool($property->isSilentFail()),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    /**
     * @param string|class-string $resource
     */
    private function getObject(string $resource): object
    {
        if (class_exists($resource)) {
            return (new ReflectionClass($resource))->newInstanceWithoutConstructor();
        }

        return (new ReflectionClass($this->resourceLocator->getEntityFromResourceType($resource)))->newInstanceWithoutConstructor();
    }
}