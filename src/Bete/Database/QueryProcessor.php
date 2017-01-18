<?php

namespace Bete\Database;

class QueryProcessor
{

    protected $builder;

    public $tablePrefix;

    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    public function __construct(QueryBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function buildSelect()
    {
        $builder = $this->builder;

        $previousColumns = $builder->columns;

        if (is_null($builder->columns)) {
            $builder->columns = ['*'];
        }

        $sql = trim($this->concatenate($this->buildComponents()));

        $builder->columns = $previousColumns;

        return $sql;
    }

    protected function buildComponents()
    {
        $sql = [];
        $builder = $this->builder;

        foreach ($this->selectComponents as $component) {
            if (!is_null($builder->$component)) {
                $method = 'build' . ucfirst($component);
                $sql[$component] = $this->$method();
            }
        }

        return $sql;
    }

    protected function buildAggregate()
    {
        $column = $this->columnize($this->builder->aggregate['columns']);

        if ($this->builder->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        $function = $this->builder->aggregate['function'];
        return "select {$function}({$column}) as aggregate";
    }

    protected function buildColumns()
    {
        $builder = $this->builder;

        if (!is_null($builder->aggregate)) {
            return ;
        }

        $select = $builder->distinct ? 'select distinct ' : 'select ';

        return $select . $this->columnize($builder->columns);
    }

    protected function buildFrom()
    {
        return 'from ' . $this->wrapTable($this->builder->from);
    }

    protected function buildWheres()
    {
        $sql = [];
        $builder = $this->builder;

        if (is_null($builder->wheres)) {
            return '';
        }

        foreach ($builder->wheres as $where) {
            $method = 'where' . ucfirst($where['type']);

            $sql[] = $where['boolean'] . ' ' . $this->$method($where);
        }

        if (count($sql) > 0) {
            $sql = implode(' ', $sql);

            return 'where ' . $this->removeLeadingBoolean($sql);
        }

        return '';
    }

    protected function whereBasic($where)
    {
        return $where['column'] . ' ' . $where['operator'] . ' ?';
    }

    protected function whereBetween($where)
    {
        $between = $where['not'] ? 'not between' : 'between';

        return $where['column'] . ' ' . $between . ' ? and ?';
    }

    protected function whereIn($where)
    {
        if (empty($where['value'])) {
            if ($where['not']) {
                return '1 = 1'; 
            } else {
                return '0 = 1';
            }
        }

        $count = count($where['value']);
        $value = implode(', ', array_fill(0, $count, '?'));

        $in = $where['not'] ? 'not in' : 'in';

        return $where['column'] . " {$in} ({$value})";
    }

    protected function whereNull($where)
    {
        $null = $where['not'] ? 'is not null' : 'is null';

        return $where['column'] . ' ' . $null;
    }

    protected function whereRaw($where)
    {
        return $where['sql'];
    }

    protected function whereColumn($where)
    {
        return $where['first'].' '.$where['operator'].' '.$where['second'];
    }

    protected function buildGroups()
    {
        $groups = $this->builder->groups;

        return 'group by ' . implode(', ', $groups);
    }

    protected function buildHavings()
    {
        $havings = $this->builder->havings;

        $sql = implode(' ', array_map([$this, 'buildHaving'], $havings));

        return 'having ' . $this->removeLeadingBoolean($sql);
    }

    protected function buildHaving(array $having)
    {
        if ($having['type'] = 'raw') {
            return $having['boolean'] . ' ' . $having['sql'];
        }

        return $this->buildBasicHaving($having);
    }

    protected function buildBasicHaving(array $having)
    {
        return $having['boolean'] . ' ' . $having['column'] . ' ' .
            $having['operator'] . ' ' . $having['value'];
    }

    protected function buildOrders()
    {
        $orders = $this->builder->orders;

        $sql = [];
        foreach ($orders as $order) {
            $sql[] = $order['column'] . ' ' . $order['direction'];
        }

        return 'order by ' . implode(', ', $sql);
    }

    protected function buildLimit()
    {
        $limit = $this->builder->limit;

        return 'limit '.(int) $limit;
    }

    protected function buildOffset()
    {
        $offset = $this->builder->offset;

        return 'offset '.(int) $offset;
    }

    public function buildInsert(array $values)
    {
        $table = $this->wrapTable($this->builder->from);

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        $parameters = [];
        foreach ($values as $record) {
            $parameters[] = '(' . $this->parameterize($record) . ')';
        }

        $parameters = implode(', ', $parameters);

        return "insert into $table ($columns) values $parameters";
    }

    public function buildInsertGetId($values)
    {
        return $this->buildInsert($values);
    }

    public function processInsertGetId($sql, $bindings, $sequence = null)
    {
        $this->builder->getConnection()->insert($sql, $bindings);

        $id = $this->builder->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? intval($id) : $id;
    }

    public function buildUpdate($values)
    {
        $builder = $this->builder;

        $table = $this->wrapTable($builder->from);

        $columns = [];

        foreach ($values as $key => $value) {
            $columns[] = $this->wrapValue($key).' = '.$this->parameter($value);
        }

        $columns = implode(', ', $columns);

        $where = $this->buildWheres();

        return trim("update {$table} set {$columns} {$where}");
    }

    public function buildDelete()
    {
        $table = $this->wrapTable($this->builder->from);

        $where = $this->buildWheres();

        return trim("delete from $table ".$where);
    }

    public function buildTruncate()
    {
        return 'truncate ' . $this->wrapTable($this->builder->from);
    }

    protected function buildLock(QueryBuilder $builder, $value)
    {
        return is_string($value) ? $value : '';
    }

    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return strval($value) !== '';
        }));
    }

    protected function wrap($value)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        if (strpos(strtolower($value), ' as ') !== false) {
            $segments = explode(' ', $value);

            return $this->wrap($segments[0]) . ' as ' . $this->wrapValue($segments[2]);
        }

        return $this->wrapValue($value);
    }

    protected function wrapTable($table)
    {
        if ($this->isExpression($table)) {
            return $this->getValue($table);
        }

        return $this->wrap($this->tablePrefix . $table);
    }

    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '`'.$value.'`';
    }

    protected function getValue($expression)
    {
        return $expression->getValue();
    }

    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    public function isExpression($value)
    {
        return $value instanceof SqlExpression;
    }

}
