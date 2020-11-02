<?php

namespace Rikudou\JsonApiBundle\Service;

use ArgumentCountError;
use function count;
use ReflectionClass;
use Rikudou\JsonApiBundle\Exception\ResourceNotFoundException;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;

final class ApiResourceLocator
{
    /**
     * @var ApiControllerInterface[]
     */
    private $controllers = [];

    private $map = [];

    /**
     * @var ApiObjectParser
     */
    private $objectParser;

    /**
     * @var Inflector
     */
    private $inflector;

    public function __construct(ApiObjectParser $objectParser, Inflector $inflector)
    {
        $this->objectParser = $objectParser;
        $this->inflector = $inflector;
    }

    /**
     * @param ApiControllerInterface $controller
     * @param string                 $serviceName
     *
     * @internal
     */
    public function addController(ApiControllerInterface $controller, string $serviceName): void
    {
        $controller->setServiceName($serviceName);
        $this->controllers[] = $controller;
    }

    /**
     * @param string $resourceName
     *
     * @throws ResourceNotFoundException
     *
     * @return ApiControllerInterface
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

    public function getEntityFromResourceType(string $resourceName): string
    {
        $controller = $this->findControllerForResource($resourceName);

        return $controller->getClass();
    }

    public function getResourceNames(bool $plural = true)
    {
        $this->populateMap();

        $result = [];
        $copy = array_filter($this->map, function ($item) use ($plural) {
            return $item['plural'] === $plural;
        });
        assert(is_array($copy));
        foreach ($copy as $key => $value) {
            $result[] = $key;
        }

        return $result;
    }

    private function populateMap()
    {
        if (!count($this->map)) {
            foreach ($this->controllers as $controller) {
                $className = $controller->getClass();

                try {
                    $names = [
                        $this->objectParser->getResourceName(new $className),
                        $this->objectParser->getPluralResourceName(new $className),
                    ];
                } catch (ArgumentCountError $error) {
                    $reflection = new ReflectionClass($className);
                    $names = [
                        $this->objectParser->getResourceName($reflection->newInstanceWithoutConstructor()),
                        $this->objectParser->getPluralResourceName($reflection->newInstanceWithoutConstructor()),
                    ];
                }

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
