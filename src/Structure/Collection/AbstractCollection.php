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
use ReflectionException;
use ReflectionProperty;
use Rikudou\JsonApiBundle\Exception\LockedCollectionException;
use function sprintf;

/**
 * @template T
 * @implements ArrayAccess<int|string, T>
 * @implements Iterator<int|string, T>
 */
abstract class AbstractCollection implements ArrayAccess, Iterator, Countable
{
    /**
     * @var array<string|int>
     */
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

    public function key(): int|string
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
        if ($keyName = $this->getKeyProperty()) {
            foreach ($this->data as $item) {
                if (!is_object($item) && !is_array($item)) {
                    continue;
                }
                $accessor = $this->getAccessor($item, $keyName);
                if ($accessor() === $offset) {
                    return true;
                }
            }

            return false;
        }

        return isset($this->data[$offset]);
    }

    /**
     * @return T
     */
    public function offsetGet($offset): mixed
    {
        if ($keyName = $this->getKeyProperty()) {
            foreach ($this->data as $item) {
                if (!is_object($item) && !is_array($item)) {
                    continue;
                }
                $accessor = $this->getAccessor($item, $keyName);
                if ($accessor() === $offset) {
                    return $item;
                }
            }

            throw new LogicException("No item for offset '{$offset}' exists");
        }

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
        if ($keyName = $this->getKeyProperty()) {
            foreach ($this->data as $key => $item) {
                if (!is_object($item) && !is_array($item)) {
                    continue;
                }
                $accessor = $this->getAccessor($item, $keyName);
                if ($accessor() === $offset) {
                    unset($this->data[$key]);

                    return;
                }
            }
        } else {
            unset($this->data[$offset]);
        }
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

    protected function getKeyProperty(): ?string
    {
        return null;
    }

    /**
     * @return array<string>
     */
    protected function getKeys(): iterable
    {
        return array_keys($this->data);
    }

    private function refresh(): void
    {
        $this->count = count($this->data);
        $this->keys = [...$this->getKeys()];
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

    private function validate(mixed $value = null): void
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

    /**
     * @param T $item
     *
     * @return (callable(): (int|string))
     */
    private function getAccessor(mixed $item, string $name): callable
    {
        assert(is_array($item) || is_object($item));

        if (is_array($item)) {
            return fn () => $item[$name] ?? throw new LogicException("No accessor for property '{$name}'");
        }

        $getters = ['is', 'get'];
        foreach ($getters as $getter) {
            $methodName = $getter . ucfirst($name);
            if (method_exists($item, $methodName)) {
                return $item->{$methodName}(...);
            }
        }

        try {
            $reflection = new ReflectionProperty($item, $name);

            return fn () => $reflection->getValue($item);
        } catch (ReflectionException $e) {
            throw new LogicException("No property '{$name}' exists in object of type '" . get_class($item) . "'");
        }
    }
}
