<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use function is_array;
use function lcfirst;
use function preg_replace_callback;
use Rikudou\JsonApiBundle\Service\Inflector;
use RuntimeException;
use function sprintf;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function ucfirst;

abstract class AbstractApiNameResolution implements ApiNameResolutionInterface
{
    /**
     * @var Inflector
     */
    protected $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
    }

    public function getResourceNamePlural(string $className): string
    {
        return $this->getSingleInflectorResult($this->inflector->pluralize($this->getResourceName($className)));
    }

    public function getGetter(string $propertyName): string
    {
        return 'get' . ucfirst($this->snakeCaseToCamelCase($propertyName));
    }

    public function getSetter(string $propertyName): string
    {
        return 'set' . ucfirst($this->snakeCaseToCamelCase($propertyName));
    }

    public function getAdder(string $propertyName): string
    {
        return 'add' . $this->getSingleInflectorResult(
            $this->inflector->singularize(ucfirst($this->snakeCaseToCamelCase($propertyName)))
        );
    }

    public function getRemover(string $propertyName): string
    {
        return 'remove' . $this->getSingleInflectorResult(
            $this->inflector->singularize(ucfirst($this->snakeCaseToCamelCase($propertyName)))
        );
    }

    public function getIsser(string $propertyName): string
    {
        return 'is' . ucfirst($this->snakeCaseToCamelCase($propertyName));
    }

    public function getHasser(string $propertyName): string
    {
        return 'has' . ucfirst($this->snakeCaseToCamelCase($propertyName));
    }

    public function getAttributeNameFromMethod(string $methodName): string
    {
        $prefixes = [
            'get',
            'has',
            'is',
        ];

        foreach ($prefixes as $prefix) {
            if (strpos($methodName, $prefix) === 0) {
                return $this->getAttributeNameFromProperty(lcfirst(substr($methodName, strlen($prefix))));
            }
        }

        throw new RuntimeException(sprintf("Cannot transform method '%s' to attribute name", $methodName));
    }

    protected function snakeCaseToCamelCase(string $propertyName): string
    {
        return (string) preg_replace_callback('@_([a-z])@', function ($matches) {
            return strtoupper($matches[1]);
        }, $propertyName);
    }

    /**
     * @param array|string $result
     *
     * @return string
     */
    private function getSingleInflectorResult($result): string
    {
        if (is_array($result)) {
            return $result[0];
        }

        return $result;
    }
}
