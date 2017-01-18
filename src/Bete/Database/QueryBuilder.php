<?php

namespace Bete\Database;

use Bete\Support\Arr;
use Bete\Support\Collection;

class QueryBuilder
{
    protected $connection;

    protected $processor;

    protected $bindings = [
        'select' => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
    ];

    public $aggregate;

    public $distinct = false;

    public $columns;

    public $from;

    public $wheres;

    public $groups;

    public $havings;

    public $orders;

    public $limit;

    public $offset;

    public $lock;

    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'not between', 'in', 'not in',
    ];

    protected $columnOperators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like',
    ];

    protected $useWritePdo = false;

    public function __construct($connection)
    {
        $this->connection = $connection;

        $this->processor = new QueryProcessor($this);
        $this->processor->tablePrefix = $connection->getTablePrefix();
    }

    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function selectRaw($sql, array $bindings = [])
    {
        $this->addSelect(new SqlExpression($sql));

        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }

        return $this;
    }

    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();

        $this->columns = array_merge((array) $this->columns, $column);

        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    public function where($column, $operator = null, $value = null, 
        $boolean = 'and')
    {
        if (is_array($column)) {
            foreach ($column as $value) {
                call_user_func_array([$this, 'where'], $value);
            }
            return $this;
        }

        if (func_num_args() == 2 || 
            !in_array(strtolower($operator), $this->operators, true)) {
            $value = $operator;
            $operator = '=';
        }
        $operator = strtolower($operator);

        if (is_null($value)) {
            $operator = ($operator == '=') ? 'is null' : 'is not null';
        }

        $not = false;
        if ($operator == 'between') {
            $type = 'Between';
        } else if ($operator == 'not between') {
            $type = 'Between';
            $not = true;
        } else if ($operator == 'in') {
            $type = 'In';
        } else if ($operator == 'not in') {
            $type = 'In';
            $not = true;
        } else if ($operator == 'is null') {
            $type = 'Null';
        } else if ($operator == 'is not null') {
            $type = 'Null';
            $not = true;
        } else {
            $type = 'Basic';
        }

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 
            'boolean', 'not');

        if (! $value instanceof SqlExpression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        $type = 'raw';

        $bindings = (array) $bindings;

        $this->wheres[] = compact('type', 'sql', 'boolean');

        $this->addBinding($bindings, 'where');

        return $this;
    }

    public function orWhereRaw($sql, $bindings = [])
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    public function whereColumn($first, $operator = null, $second = null, 
        $boolean = 'and')
    {
        if (is_array($first)) {
            foreach ($first as $value) {
                call_user_func_array([$this, 'whereColumn'], $value);
            }
            return $this;
        }

        if (func_num_args() == 2 || 
            !in_array(strtolower($operator), $this->columnOperators, true)) {
            $value = $operator;
            $operator = '=';
        }
        $operator = strtolower($operator);

        $type = 'Column';

        $this->wheres[] = compact('type', 'first', 'operator', 'second', 
            'boolean');
        return $this;
    }

    public function orWhereColumn($first, $operator = null, $second = null)
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    public function groupBy()
    {
        $groups = func_get_args();
        foreach ($groups as $group) {
            $this->groups = array_merge((array)$this->groups, 
                is_array($group) ? $group : [$group]);
        }

        return $this;
    }

    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'basic';

        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

        return $this;
    }

    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }

    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'raw';

        $this->havings[] = compact('type', 'sql', 'boolean');

        $this->addBinding($bindings, 'having');

        return $this;
    }

    public function orHavingRaw($sql, array $bindings = [])
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => (strtolower($direction) == 'asc' ? 'asc' : 'desc'),
        ];

        return $this;
    }

    public function orderByRaw($sql, $bindings = [])
    {
        $property = 'orders';

        $type = 'raw';

        $this->orders[] = compact('type', 'sql');

        $this->addBinding($bindings, 'order');

        return $this;
    }

    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    public function limit($value)
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    public function toSql()
    {
        return $this->processor->buildSelect($this);
    }

    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    public function all($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $previousColumns = $this->columns;

        if (is_null($previousColumns)) {
            $this->columns = $columns;
        }

        $results = $this->runSelect();

        $this->columns = $previousColumns;

        return new Collection($results);
    }

    public function first($columns = ['*'])
    {
        return $this->limit(1)->all($columns)->first();
    }

    public function column($column)
    {
        $array = [];

        $results = $this->all($column);

        if (!$results->isEmpty()) {
            foreach ($results as $item) {
                $item = (array) $item;
                $array[] = $item[$column];
            }
        }

        return $array;
    }

    public function value($column)
    {
        $result = (array) $this->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    public function cursor()
    {
        if (is_null($this->columns)) {
            $this->columns = ['*'];
        }

        return $this->connection->cursor(
            $this->toSql(), $this->getBindings(), !$this->useWritePdo
        );
    }

    public function chunk($count, callable $callback)
    {
        $results = $this->onePage($page = 1, $count)->all();

        while (!$results->isEmpty()) {
            if (call_user_func($callback, $results) === false) {
                return false;
            }

            $page++;

            $results = $this->onePage($page, $count)->all();
        }

        return true;
    }

    public function onePage($page, $pageSize = 15)
    {
        return $this->offset(($page - 1) * $pageSize)->limit($pageSize);
    }

    public function exists()
    {

    }

    public function count($columns = '*')
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        return (int) $this->aggregate(__FUNCTION__, $columns);
    }

    public function min($column)
    {
        return (int) $this->aggregate(__FUNCTION__, [$column]);
    }

    public function max($column)
    {
        return (int) $this->aggregate(__FUNCTION__, [$column]);
    }

    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ? $result : 0;
    }

    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function aggregate($function, $columns = ['*'])
    {
        $this->aggregate = compact('function', 'columns');

        $previousColumns = $this->columns;

        $previousSelectBindings = $this->bindings['select'];

        $this->bindings['select'] = [];

        $result = $this->first($columns);

        $this->aggregate = null;

        $this->columns = $previousColumns;

        $this->bindings['select'] = $previousSelectBindings;

        if ($result) {
            $result = array_change_key_case((array) $result);
            return $result['aggregate'];
        }
    }

    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }

        $sql = $this->processor->buildInsert($values);

        $bindings = $this->cleanBindings($bindings);

        return $this->connection->insert($sql, $bindings);

    }

    public function insertGetId($values, $sequence = null)
    {
        $sql = $this->processor->buildInsertGetId($values, $sequence);

        $values = $this->cleanBindings($values);

        return $this->processor->processInsertGetId($sql, $values);
    }

    public function update(array $values)
    {
        $bindings = array_values(array_merge($values, $this->getBindings()));

        $sql = $this->processor->buildUpdate($values);

        return $this->connection->update($sql, $this->cleanBindings($bindings));
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        if (!is_numeric($amount)) {
            throw new Exception("Error Processing Request", 1);
        }

        $values = array_merge(
            [$column => $this->raw("{$column} + {$amount}")], $extra
        );

        return $this->update($values);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (!is_numeric($amount)) {
            throw new Exception("Error Processing Request", 1);
        }

        $values = array_merge(
            [$column => $this->raw("{$column} - {$amount}")], $extra
        );

        return $this->update($values);
    }

    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where('id', '=', $id);
        }

        $sql = $this->processor->buildDelete($this);

        return $this->connection->delete($sql, $this->getBindings());
    }

    public function truncate()
    {
        $sql = $this->processor->buildTruncate();

        return $this->connection->statement($sql);
    }

    public function lock($value = true)
    {
        $this->lock = $value;

        if ($this->lock) {
            $this->useWritePdo();
        }

        return $this;
    }

    public function lockForUpdate()
    {
        return $this->lock(true);
    }

    public function sharedLock()
    {
        return $this->lock(false);
    }

    public function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new Exception("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    protected function cleanBindings(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return ! $binding instanceof SqlExpression;
        }));
    }

    public function raw($value)
    {
        return $this->connection->raw($value);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getProcessor()
    {
        return $this->processor;
    }

    public function useWritePdo()
    {
        $this->useWritePdo = true;

        return $this;
    }

    public function getBindings()
    {
        return Arr::flatten($this->bindings);
    }

    public function runSelect()
    {
        return $this->connection->select($this->toSql(), $this->getBindings(), 
            !$this->useWritePdo);
    }

}


