<?php

namespace Rikudou\JsonApiBundle\Service;

use function count;
use Rikudou\JsonApiBundle\Exception\ResourceNotFoundException;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;
use Symfony\Component\Inflector\Inflector;

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

    public function __construct(ApiObjectParser $objectParser)
    {
        $this->objectParser = $objectParser;
    }

    /**
     * @param ApiControllerInterface $controller
     *
     * @internal
     */
    public function addController(ApiControllerInterface $controller): void
    {
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
            if (isset($this->map[Inflector::pluralize($resourceName)])) {
                $resourceName = Inflector::pluralize($resourceName);
            } elseif (isset($this->map[Inflector::singularize($resourceName)])) {
                $resourceName = Inflector::singularize($resourceName);
            } else {
                throw new ResourceNotFoundException($resourceName);
            }
        }

        return $this->map[$resourceName];
    }

    public function getResourceNames(bool $plural = true)
    {
        $this->populateMap();

        $result = [];
        foreach ($this->map as $key => $value) {
            $result[] = $plural ? Inflector::pluralize($key) : $key;
        }

        return $result;
    }

    private function populateMap()
    {
        if (!count($this->map)) {
            foreach ($this->controllers as $controller) {
                $className = $controller->getClass();
                $this->map[$this->objectParser->getResourceName(new $className)] = $controller;
            }
        }
    }
}
