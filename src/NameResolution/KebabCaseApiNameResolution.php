<?php

namespace Rikudou\JsonApiBundle\NameResolution;

use JetBrains\PhpStorm\Pure;
use function preg_replace_callback;
use ReflectionException;
use Rikudou\JsonApiBundle\Service\Inflector;
use function strtolower;

final class KebabCaseApiNameResolution extends AbstractApiNameResolution
{
    #[Pure]
    public function __construct(
        Inflector $inflector,
        private CamelCaseApiNameResolution $camelCase,
    ) {
        parent::__construct($inflector);
    }

    /**
     * @param class-string $className
     *
     * @throws ReflectionException
     */
    public function getResourceName(string $className): string
    {
        return $this->camelCase->getResourceName($className);
    }

    public function getAttributeNameFromProperty(string $propertyName): string
    {
        $normalized = $this->snakeCaseToCamelCase($propertyName);

        return (string) preg_replace_callback(
            '@([A-Z])@',
            fn ($matches): string => '-' . strtolower($matches[1]),
            $normalized,
        );
    }
}
