<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use function assert;
use function is_string;
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
     * Transforms the class name to resource name.
     *
     * Example:
     *   Activity      -> activity
     *   ActivityState -> activity-state
     *
     * @param string $className
     *
     * @throws ReflectionException
     *
     * @return string
     */
    public function getResourceName(string $className): string
    {
        $reflection = new ReflectionClass($className);

        $name = lcfirst(substr($reflection->getName(), strlen($reflection->getNamespaceName()) + 1));
        $name = preg_replace_callback('@([A-Z])@', function ($matches) {
            return '-' . strtolower($matches[1]);
        }, $name);

        assert(is_string($name));

        return $name;
    }

    /**
     * Transforms the property name to attribute name.
     *
     * Examples:
     *   myProperty  -> myProperty
     *   myProperty  -> my_property
     *   my_property -> myProperty
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getAttributeNameFromProperty(string $propertyName): string
    {
        return $this->snakeCaseToCamelCase($propertyName);
    }
}
