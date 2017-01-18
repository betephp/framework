<?php

namespace Bete\Foundation;

use Exception;
use Closure;
use ArrayAccess;
use ReflectionClass;

class Container implements ArrayAccess
{
    protected $bindings = [];

    protected $instances = [];

    protected $aliases = [];

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!($concrete instanceof Closure)) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

    }

    public function getClosure($abstract, $concrete)
    {
        return function ($app, $parameters = []) use ($abstract, $concrete) {
            $method = ($abstract == $concrete) ? 'build' : 'make';
            return $app->$method($concrete, $parameters);
        };
    }

    public function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    public function make($abstract, array $params = [])
    {
        $abstract = $this->getAlias($this->normalize($abstract));

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $params);
        } else {
            $object = $this->make($concrete, $params);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        // If parameters is empty, reslove the dependency for constructor's
        // parameters, otherwise pass parameters to constructor.
        if (empty($parameters)) {
            $reflectedParameters = $constructor->getParameters();
            $instances = $this->getDependencies($reflectedParameters);
        } else {
            // If the parameters number is less than the constructor's
            // required parameters number, then throw Exception.
            $number = $constructor->getNumberOfRequiredParameters();
            if (count($parameters) < $number) {
                $message = "The parameters is not suited for {$concrete} construct.";
                throw new BindingResolutionException($message);
            }

            $instances = $parameters;
        }

        return $reflector->newInstanceArgs($instances);
    }

    protected function getDependencies(array $reflectedParams)
    {
        $dependencies = [];

        foreach ($reflectedParams as $reflectedParam) {
            if ($reflectedParam->isOptional()) {
                $dependencies[] = $reflectedParam->getgetDefaultValue();
            } else {
                $dependency = $reflectedParam->getClass();
                if (is_null($dependency)) {
                    throw new Exception("Can not reslove non-class type.");
                }

                $dependencies[] =  $this->make($dependency->name);
            }
        }

        return $dependencies;
    }

    protected function isBuildable($concrete, $abstract)
    {
        return ($concrete === $abstract) || ($concrete instanceof Closure);
    }

    public function isShared($abstract)
    {
        $abstract = $this->normalize($abstract);

        if (isset($this->instances[$abstract])) {
            return true;
        }

        if (!isset($this->bindings[$abstract]['shared'])) {
            return false;
        }

        return $this->bindings[$abstract]['shared'] === true;
    }

    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }

    public function instance($abstract, $instance)
    {
        $abstract = $this->normalize($abstract);

        unset($this->aliases[$abstract]);
        
        $this->instances[$abstract] = $instance;
    }

    protected function normalize($name)
    {
        return is_string($name) ? ltrim($name, '\\') : $name;
    }

    protected function getConcrete($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $this->normalize($abstract);
    }

    protected function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    public function isBound($abstract)
    {
        $abstract = $this->normalize($abstract);

        return isset($this->bindings[$abstract]) || 
            isset($this->instances[$abstract]) || 
            $this->isAlias($abstract);
    }

    public function offsetExists($key)
    {
        return $this->isBound($key);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    public function offsetUnset($key)
    {
        $key = $this->normalize($key);

        unset($this->bindings[$key], $this->instances[$key], $this->aliases[$key]);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
