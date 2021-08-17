<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use function lcfirst;
use function preg_replace_callback;
use Rikudou\JsonApiBundle\Service\Inflector;
use RuntimeException;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function ucfirst;

abstract class AbstractApiNameResolution implements ApiNameResolutionInterface
{
    public function __construct(protected Inflector $inflector)
    {
    }

    public function getResourceNamePlural(string $className): string
    {
        return $this->inflector->pluralize($this->getResourceName($className));
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
        return 'add' .
            $this->inflector->singularize(ucfirst($this->snakeCaseToCamelCase($propertyName)))
            ;
    }

    public function getRemover(string $propertyName): string
    {
        return 'remove' .
            $this->inflector->singularize(ucfirst($this->snakeCaseToCamelCase($propertyName)))
            ;
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
            if (str_starts_with($methodName, $prefix)) {
                return $this->getAttributeNameFromProperty(lcfirst(substr($methodName, strlen($prefix))));
            }
        }

        throw new RuntimeException(sprintf("Cannot transform method '%s' to attribute name", $methodName));
    }

    protected function snakeCaseToCamelCase(string $propertyName): string
    {
        return (string) preg_replace_callback(
            '@_([a-z])@',
            fn ($matches): string => strtoupper($matches[1]),
            $propertyName,
        );
    }
}
