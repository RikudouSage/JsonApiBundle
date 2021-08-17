<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function call_user_func;
use InvalidArgumentException;
use function is_callable;
use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;

final class ApiObjectAccessor
{
    public const TYPE_PROPERTY = 2 << 1;

    public const TYPE_METHOD = 2 << 2;

    public function __construct(
        #[ExpectedValues(valuesFromClass: self::class)]
        private int $type,
        private string $getter,
        private ?string $setter,
        private ?string $adder,
        private ?string $remover,
        private ?bool $isRelation,
        private bool $readonly,
        private bool $silentFail,
    ) {
    }

    #[ExpectedValues(valuesFromClass: self::class)]
    public function getType(): int
    {
        return $this->type;
    }

    public function getGetter(): string
    {
        return $this->getter;
    }

    public function getSetter(): ?string
    {
        return $this->setter;
    }

    public function getAdder(): ?string
    {
        return $this->adder;
    }

    public function getRemover(): ?string
    {
        return $this->remover;
    }

    public function isRelation(): ?bool
    {
        return $this->isRelation;
    }

    public function getValue(object $object): mixed
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

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function isSilentFail(): bool
    {
        return $this->silentFail;
    }
}
