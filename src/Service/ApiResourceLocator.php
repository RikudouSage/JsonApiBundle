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
            $found = false;

            $plural = $this->inflector->pluralize($resourceName);
            $singular = $this->inflector->singularize($resourceName);

            if (!is_array($plural)) {
                $plural = [$plural];
            }
            if (!is_array($singular)) {
                $singular = [$singular];
            }

            $result = array_merge($plural, $singular);

            foreach ($result as $word) {
                if (isset($this->map[$word])) {
                    $resourceName = $word;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new ResourceNotFoundException($resourceName);
            }
        }

        return $this->map[$resourceName];
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
        foreach ($this->map as $key => $value) {
            $result[] = $plural ? $this->inflector->pluralize($key) : $key;
        }

        return $result;
    }

    private function populateMap()
    {
        if (!count($this->map)) {
            foreach ($this->controllers as $controller) {
                $className = $controller->getClass();

                try {
                    $this->map[$this->objectParser->getResourceName(new $className)] = $controller;
                } catch (ArgumentCountError $error) {
                    $reflection = new ReflectionClass($className);
                    $this->map[
                        $this->objectParser->getResourceName($reflection->newInstanceWithoutConstructor())
                    ] = $controller;
                }
            }
        }
    }
}
