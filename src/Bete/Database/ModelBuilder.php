<?php

namespace Bete\Database;

class ModelBuilder
{

    protected $query;

    protected $model;

    protected $convenientMethods = [
        'insert', 'insertGetId', 'getBindings', 'toSql',
        'count', 'min', 'max', 'avg', 'sum', 'getConnection',
    ];

    public function __construct(QueryBuilder $query, Model $model)
    {
        $this->query = $query;

        $this->setModel($model);
    }

    public function setModel(Model $model)
    {
        $this->model = $model;


        $this->query->from($model->getTable());

        return $this;
    }

    public function find($id, $columns = ['*'])
    {
        $this->query->where($this->model->getPrimaryKey(), '=', $id);

        return $this->first($columns);
    }

    public function first($columns = ['*'])
    {
        return $this->limit(1)->all($columns)->first();
    }

    public function all($columns = ['*'])
    {
        $models = $this->getModels($columns);

        return $this->getModel()->newCollection($models);
    }

    public function getModels($columns = ['*'])
    {
        $results = $this->query->all($columns)->all();

        return $this->model->makeModels($results)->all();
    }

    public function getModel()
    {
        return $this->model;
    }

    public function where($column, $operator = null, $value = null, 
        $boolean = 'and')
    {
        call_user_func_array([$this->query, 'where'], func_get_args());

        return $this;
    }

    public function delete()
    {
        return $this->query->delete();
    }

    public function value($column)
    {
        $result = $this->first([$column]);

        if ($result) {
            return $result->{$column};
        }
    }

    public function chunk($count, callable $callback)
    {
        $results = $this->onePage($page = 1, $count)->all();

        while (! $results->isEmpty()) {
            if (call_user_func($callback, $results) === false) {
                return false;
            }

            $page++;
            $results = $this->onePage($page, $count)->all();
        }

        return true;
    }

    public function cursor()
    {

    }

    public function __call($method, $parameters)
    {
        if (in_array($method, $this->convenientMethods)) {
            return call_user_func_array([$this->query, $method], $parameters);
        }

        call_user_func_array([$this->query, $method], $parameters);

        return $this;
    }

}