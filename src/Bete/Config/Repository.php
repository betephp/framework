<?php

namespace Bete\Config;

use ArrayAccess;
use Bete\Support\Arr;

class Repository implements ArrayAccess
{
    
	protected $items = [];

	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	public function get($key, $defaultValue = null)
	{
        return Arr::get($this->items, $key, $defaultValue);
	}

	public function set($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->items[$k] = $v;
			}
		} else {
			$this->items[$key] = $value;
		}
	}

	public function has($key)
	{
        return Arr::has($this->items, $key);
	}

	public function items()
	{
		return $this->items;
	}

    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
