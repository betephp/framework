<?php

namespace Bete\Support;

use Countable;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;

class Collection implements Countable, ArrayAccess, IteratorAggregate
{
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } else if ($items instanceof self) {
            return $items->all();
        } else if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    public function all()
    {
        return $this->items;
    }

    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return value($default);
    }

    public function has($key)
    {
        return $this->offsetExists($key);
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function keys()
    {
        return new static(array_keys($this->items));
    }

    public function values()
    {
        return new static(array_values($this->items));
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function push($value)
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    public function each(callable $callable)
    {
        foreach ($this->items as $key => $item) {
            if ($callable($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function chunk($size)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback
            ? uasort($items, $callback)
            : asort($items);

        return new static($items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

}