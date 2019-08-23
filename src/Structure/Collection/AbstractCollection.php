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

abstract class AbstractCollection implements ArrayAccess, Iterator, Countable
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $keys;

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var bool
     */
    private $locked = false;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->validate();
        $this->refresh();
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function add($value)
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    public function current()
    {
        return $this->offsetGet($this->keys[$this->current]);
    }

    public function next()
    {
        ++$this->current;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->keys[$this->current] ?? -1;
    }

    public function valid()
    {
        return isset($this->keys[$this->current]) && $this->offsetExists($this->keys[$this->current]);
    }

    public function rewind()
    {
        $this->current = 0;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
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

    public function offsetUnset($offset)
    {
        if ($this->locked) {
            throw new LockedCollectionException();
        }
        unset($this->data[$offset]);
        $this->refresh();
    }

    public function count()
    {
        return $this->count;
    }

    /**
     * Returns the allowed type of value for this collection.
     * Return null to allow every type.
     *
     * @return array|null
     */
    abstract protected function getAllowedTypes(): ?array;

    private function refresh()
    {
        $this->count = count($this->data);
        $this->keys = array_keys($this->data);
        $this->rewind();
    }

    private function lock()
    {
        $this->locked = true;
    }

    private function unlock()
    {
        $this->locked = false;
    }

    /**
     * @param mixed $value
     */
    private function validate($value = null)
    {
        if ($this->getAllowedTypes() === null) {
            return;
        }

        /** @var string[] $allowedTypes */
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
                    sprintf('The collection accepts only instances of %s', implode(', ', $allowedTypes))
                );
            }
        }
    }
}
