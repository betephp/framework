<?php

namespace Bete\Database;

use Bete\Support\Collection;

abstract class Model 
{
    protected $connection;

    protected $table;

    protected $primaryKey = 'id';

    protected $increment = true;

    protected $pageNum;

    protected $attributes = [];

    protected $original = [];

    public $exists = false;

    protected static $resolver;

    public function __construct(array $attributes = [])
    {
        $this->syncOriginal();

        $this->fill($attributes);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function fill(array $attributes)
    {
        $this->setAttributes($attributes);

        return $this;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key)
    {
        return $this->attributes[$key];
    }

    public function setAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public static function destroy($ids)
    {
        $count = 0;

        $ids = is_array($ids) ? $ids : func_get_args();

        $instance = new static;

        $key = $instance->getPrimaryKey();

        foreach ($instance->where($key, 'in', $ids)->all() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    public function update(array $attributes = [])
    {
        if (!$this->exists) {
            return false;
        }

        return $this->fill($attributes)->save();
    }

    public function save()
    {
        $builder = $this->newBuilder();

        if ($this->exists) {
            $saved = $this->isModified() ? 
                $this->performUpdate($builder) : true;
        } else {
            $saved = $this->performInsert($builder);
        }

        if ($saved) {
            $this->finishSave();
        }

        return $saved;
    }

    public function finishSave()
    {
        $this->syncOriginal();
    }

    protected function performInsert(ModelBuilder $builder)
    {
        $attributes = $this->attributes;

        if ($this->getIncrement()) {
            $this->insertAndSetId($builder, $attributes);
        } else {
            $builder->insert($attributes);
        }

        $this->exists = true;

        return true;
    }

    public function performUpdate(ModelBuilder $builder)
    {
        $modified = $this->getModified();

        if (count($modified) > 0) {
            $this->setIdCondition($builder)->update($modified);
        }

        return true;
    }

    public function delete()
    {
        $builder = $this->newBuilder();

        if (is_null($this->getPrimaryKey())) {
            throw new DatabaseException("No primary key defined on model.");
        }

        if ($this->exists) {
            $this->performDelete($builder);

            $this->exists = false;

            return true;
        }
    }

    protected function performDelete(ModelBuilder $builder)
    {
        $builder = $this->newBuilder();

        $this->setIdCondition($builder)->delete();        
    }

    protected function insertAndSetId(ModelBuilder $builder, $attributes)
    {
        $primaryKey = $this->getPrimaryKey();

        $id = $builder->insertGetId($attributes, $primaryKey);

        $this->setAttribute($primaryKey, $id);
    }

    public function setIdCondition(ModelBuilder $builder)
    {
        $builder->where($this->getPrimaryKey(), '=', 
            $this->getIdValue());

        return $builder;
    }

    protected function getIdValue()
    {
        if (isset($this->original[$this->getPrimaryKey()])) {
            return $this->original[$this->getPrimaryKey()];
        }

        return $this->getAttribute($this->getPrimaryKey());
    }

    public function getIncrement()
    {
        return $this->increment;
    }

    public function setIncrement($increment)
    {
        $this->increment = $increment;

        return $this;
    }

    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    public static function all($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $instance = new static();

        return $instance->newBuilder()->all($columns);
    }

    public function makeModels(array $items)
    {
        $items = array_map(function($item) {
            return $this->newFromBuilder($item);
        }, $items);

        return $this->newCollection($items);
    }

    public function newFromBuilder($attributes = [])
    {
        $model = $this->newInstance($attributes, true);

        return $model;
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static();

        $model->setAttributes((array) $attributes);

        $model->exists = $exists;

        return $model;
    }

    public function newBuilder()
    {
        $connection = $this->getConenction();

        $queryBuilder = new QueryBuilder($connection);

        $modelBuilder = new ModelBuilder($queryBuilder, $this);

        return $modelBuilder;
    }

    protected function newQueryBuilder()
    {
        $connection = $this->getConenction();

        return new QueryBuilder($connection);
    }

    protected function newModelBuilder($builder)
    {
        return new ModelBuilder($builder);
    }

    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return str_replace('\\', '', Str::snake(class_basename($this)));
    }

    public function getConenction()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    public function getConnectionName()
    {
        return $this->connection;
    }

    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    public static function getConenctionResolver()
    {
        return static::$resolver;
    }

    public static function setConnectionResolver($resolver)
    {
        static::$resolver = $resolver;
    }

    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    public function isModified()
    {
        $modified = $this->getModified();

        return count($modified) > 0;
    }

    public function getModified()
    {
        $modified = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) ||
                $value !== $this->original[$key]) {
                $modified[$key] = $value;
            }
        }

        return $modified;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        $query = $this->newBuilder();

        return call_user_func_array([$query, $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

}
