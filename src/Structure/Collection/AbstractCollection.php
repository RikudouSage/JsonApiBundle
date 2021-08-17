<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_filter;
use function array_keys;
use ArrayAccess;
use function class_exists;
use Countable;
use function implode;
use InvalidArgumentException;
use function is_a;
use Iterator;
use LogicException;
use Rikudou\JsonApiBundle\Exception\LockedCollectionException;
use function sprintf;

/**
 * @template() T
 */
abstract class AbstractCollection implements ArrayAccess, Iterator, Countable
{
    protected array $keys = [];

    protected int $current = 0;

    protected int $count = 0;

    private bool $locked = false;

    /**
     * @param T[] $data
     */
    public function __construct(protected array $data = [])
    {
        $this->validate();
        $this->refresh();
    }

    /**
     * @param T $value
     */
    public function add(mixed $value): static
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this->offsetGet($this->keys[$this->current]);
    }

    public function next(): void
    {
        ++$this->current;
    }

    public function key(): int
    {
        return $this->keys[$this->current] ?? -1;
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->current]) && $this->offsetExists($this->keys[$this->current]);
    }

    public function rewind(): void
    {
        $this->current = 0;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @return T
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * @param T $value
     */
    public function offsetSet($offset, mixed $value): void
    {
        if ($this->locked) {
            throw new LockedCollectionException();
        }
        $this->validate($value);
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
        $this->refresh();
    }

    public function offsetUnset($offset): void
    {
        if ($this->locked) {
            throw new LockedCollectionException();
        }
        unset($this->data[$offset]);
        $this->refresh();
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * Returns the allowed type of value for this collection.
     * Return null to allow every type.
     *
     * @return string[]|null
     */
    abstract protected function getAllowedTypes(): ?array;

    private function refresh()
    {
        $this->count = count($this->data);
        $this->keys = array_keys($this->data);
        $this->rewind();
    }

    private function lock(): void
    {
        $this->locked = true;
    }

    private function unlock(): void
    {
        $this->locked = false;
    }

    private function validate(mixed $value = null)
    {
        if ($this->getAllowedTypes() === null) {
            return;
        }

        $allowedTypes = $this->getAllowedTypes();

        $allowedTypes = array_filter($allowedTypes, function (string $class) {
            if (!class_exists($class)) {
                throw new LogicException("{$class} is not a valid class");
            }

            return true;
        });

        if ($value === null) {
            $data = $this->data;
        } else {
            $data = [$value];
        }

        foreach ($data as $value) {
            $success = false;
            foreach ($allowedTypes as $allowedType) {
                if (is_a($value, $allowedType)) {
                    $success = true;
                }
            }
            if (!$success) {
                throw new InvalidArgumentException(
                    sprintf('The collection accepts only instances of %s', implode(', ', $allowedTypes)),
                );
            }
        }
    }
}
