<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use function lcfirst;
use function preg_replace_callback;
use ReflectionClass;
use ReflectionException;
use function strlen;
use function strtolower;
use function substr;

final class CamelCaseApiNameResolution extends AbstractApiNameResolution
{
    /**
     * @param class-string $className
     *
     * @throws ReflectionException
     */
    public function getResourceName(string $className): string
    {
        $reflection = new ReflectionClass($className);

        $name = lcfirst(substr($reflection->getName(), strlen($reflection->getNamespaceName()) + 1));

        return (string) preg_replace_callback(
            '@([A-Z])@',
            fn ($matches): string => '-' . strtolower($matches[1]),
            $name,
        );
    }

    public function getAttributeNameFromProperty(string $propertyName): string
    {
        return $this->snakeCaseToCamelCase($propertyName);
    }
}
