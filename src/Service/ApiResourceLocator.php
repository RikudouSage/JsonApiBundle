<?php

namespace Rikudou\JsonApiBundle\Service;

use ArgumentCountError;
use function count;
use ReflectionClass;
use ReflectionException;
use Rikudou\JsonApiBundle\Exception\ResourceNotFoundException;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;

final class ApiResourceLocator
{
    /**
     * @var ApiControllerInterface[]
     */
    private array $controllers = [];

    /**
     * @var array<string, array{'controller': ApiControllerInterface, 'plural': bool}>
     */
    private array $map = [];

    public function __construct(private ApiObjectParser $objectParser)
    {
    }

    /**
     * @internal
     */
    public function addController(ApiControllerInterface $controller, string $serviceName): void
    {
        $controller->setServiceName($serviceName);
        $this->controllers[] = $controller;
    }

    /**
     * @throws ReflectionException
     * @throws ResourceNotFoundException
     */
    public function findControllerForResource(string $resourceName): ApiControllerInterface
    {
        $this->populateMap();

        if (!isset($this->map[$resourceName])) {
            throw new ResourceNotFoundException($resourceName);
//            $found = false;
//
//            $plural = [$this->inflector->pluralize($resourceName)];
//            $singular = [$this->inflector->singularize($resourceName)];
//
//            $result = array_merge($plural, $singular);
//
//            foreach ($result as $word) {
//                if (isset($this->map[$word])) {
//                    $resourceName = $word;
//                    $found = true;
//                    break;
//                }
//            }
//
//            if (!$found) {
//                throw new ResourceNotFoundException($resourceName);
//            }
        }

        return $this->map[$resourceName]['controller'];
    }

    /**
     * @throws ResourceNotFoundException
     * @throws ReflectionException
     *
     * @return class-string
     */
    public function getEntityFromResourceType(string $resourceName): string
    {
        $controller = $this->findControllerForResource($resourceName);

        return $controller->getClass();
    }

    /**
     * @throws ReflectionException
     *
     * @return string[]
     */
    public function getResourceNames(bool $plural = true): array
    {
        $this->populateMap();

        $result = [];
        $copy = array_filter($this->map, fn (array $item): bool => $item['plural'] === $plural);
        foreach ($copy as $key => $value) {
            $result[] = $key;
        }

        return $result;
    }

    /**
     * @throws ReflectionException
     */
    private function populateMap(): void
    {
        if (!count($this->map)) {
            foreach ($this->controllers as $controller) {
                $className = $controller->getClass();

                try {
                    $names = [
                        $this->objectParser->getResourceName(new $className),
                        $this->objectParser->getPluralResourceName(new $className),
                    ];
                } catch (ArgumentCountError) {
                    $reflection = new ReflectionClass($className);
                    $names = [
                        $this->objectParser->getResourceName($reflection->newInstanceWithoutConstructor()),
                        $this->objectParser->getPluralResourceName($reflection->newInstanceWithoutConstructor()),
                    ];
                }
                $names = array_filter($names);

                $i = 0;
                foreach ($names as $name) {
                    $this->map[$name] = [
                        'controller' => $controller,
                        'plural' => $i % 2 === 1,
                    ];
                    ++$i;
                }
            }
        }
    }
}
