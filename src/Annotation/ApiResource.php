<?php

namespace Rikudou\JsonApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ApiResource
{
    /**
     * Sets the name of the resource, will be guessed automatically if empty
     *
     * @var string
     */
    public $name;

    /**
     * Name of properties that shouldn't be treated as api properties.
     * Useful when the parent class has api properties but you don't want to show them on children.
     *
     * @var string[]
     */
    public $disabledProperties = [];

    /**
     * Properties that you want to enable additionally.
     * Useful when you can't (or don't want to) edit the parent class but you need to include its properties in api.
     *
     * @var ApiProperty[]
     */
    public $enabledProperties = [];
}
