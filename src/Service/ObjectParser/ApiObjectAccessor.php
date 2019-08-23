<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function call_user_func;
use InvalidArgumentException;
use function is_callable;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;

final class ApiObjectAccessor
{
    public const TYPE_PROPERTY = 2 << 1;

    public const TYPE_METHOD = 2 << 2;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $getter;

    /**
     * @var string|null
     */
    private $setter;

    /**
     * @var string|null
     */
    private $adder;

    /**
     * @var string|null
     */
    private $remover;

    /**
     * @var bool|null
     */
    private $isRelation;

    public function __construct(
        int $type,
        string $getter,
        ?string $setter,
        ?string $adder,
        ?string $remover,
        ?bool $isRelation
    ) {
        $this->type = $type;
        $this->getter = $getter;
        $this->setter = $setter;
        $this->adder = $adder;
        $this->remover = $remover;
        $this->isRelation = $isRelation;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getGetter(): string
    {
        return $this->getter;
    }

    /**
     * @return string|null
     */
    public function getSetter(): ?string
    {
        return $this->setter;
    }

    /**
     * @return string|null
     */
    public function getAdder(): ?string
    {
        return $this->adder;
    }

    /**
     * @return string|null
     */
    public function getRemover(): ?string
    {
        return $this->remover;
    }

    /**
     * @return bool|null
     */
    public function isRelation(): ?bool
    {
        return $this->isRelation;
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function getValue($object)
    {
        switch ($this->getType()) {
            case self::TYPE_METHOD:
                $callable = [$object, $this->getGetter()];
                if (!is_callable($callable)) {
                    throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_GETTER);
                }

                return call_user_func($callable);
            case self::TYPE_PROPERTY:
                return $object->{$this->getGetter()};
            default:
                throw new InvalidArgumentException('The type is not a valid type');
        }
    }
}
