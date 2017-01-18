<?php

namespace Bete\Validation;

class MessageBag
{
    protected $messages = [];

    protected $format = ':message';

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $this->messages[$key] = (array) $value;
        }
    }

    public function keys()
    {
        return array_keys($this->messages);
    }

    public function first()
    {
        foreach ($this->messages as $value) {
            return $value;
        }

        return null;
    }

    public function set($key, $message)
    {
        $this->messages[$key] = $message;

        return $this;
    }

    public function isUnique($key, $message)
    {
        return !isset($this->messages[$key]) ||
            !in_array($message, $this->messages[$key]);
    }

    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }

        return true;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->messages)) {
            return $this->transform($this->messages[$key], $key);
        }

        return [];
    }

    public function all()
    {
        $all = [];

        foreach ($this->messages as $key => $message) {
            $all[$key] = $message;
        }

        return $all;
    }
    
    public function transform($messages, $key)
    {
        $messages = (array) $messages;

        return str_replace($search, [$message, $key], ':message');
    }

    public function isEmpty()
    {
        return count($this->messages) === 0;
    }

    public function toArray()
    {
        return $this->messages;
    }

}
