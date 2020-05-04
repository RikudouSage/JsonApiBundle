<?php

namespace Rikudou\JsonApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
final class ApiProperty
{
    /**
     * The property name, will be constructed automatically if not set
     *
     * @var string
     */
    public $name;

    /**
     * The setter method, will be guessed automatically if not set
     *
     * @var string
     */
    public $setter = null;

    /**
     * The adder method, will be guessed automatically if not set
     *
     * @var string
     */
    public $adder = null;

    /**
     * The getter method, will be guessed automatically if not set
     *
     * @var string
     */
    public $getter = null;

    /**
     * The remover method, will be guessed automatically if not set
     *
     * @var string
     */
    public $remover = null;

    /**
     * Sets whether the property should be treated as relation or not.
     *
     * Defaults to null which means auto-detect.
     *
     * @var bool
     */
    public $relation = null;

    /**
     * Whether the property is readonly or not
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * Whether unsupported operation should fail silently (e.g. trying to set property with no setter)
     *
     * @var bool
     */
    public $silentFail = false;
}
