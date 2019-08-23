<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use function preg_replace_callback;
use ReflectionException;
use function strtolower;

final class KebabCaseApiNameResolution extends AbstractApiNameResolution
{
    /**
     * @var CamelCaseApiNameResolution
     */
    private $camelCase;

    public function __construct(CamelCaseApiNameResolution $camelCase)
    {
        $this->camelCase = $camelCase;
    }

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
        return $this->camelCase->getResourceName($className);
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
        $normalized = $this->snakeCaseToCamelCase($propertyName);

        return preg_replace_callback('@([A-Z])@', function ($matches) {
            return '-' . strtolower($matches[1]);
        }, $normalized);
    }
}
