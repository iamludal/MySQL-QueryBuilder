<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Clauses\ClauseMethods;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Select extends Clause implements ClauseMethods
{
    private $table; // the table from which to select
    private $columns = []; // columns to select
    private $conditions = []; // WHERE conditions
    private $order = []; // ORDER BY columns

    /**
     * Specify the columns to select.
     * 
     * Each column should be either a string, which is the name of the column,
     * or an array of length 2 of the form : [$columnName, $alias] (where
     * $columnName and $alias are strings)
     * 
     * @param string|array ...$columns (optional) the columns to select. Default: '*'
     * @return Select the instance
     */
    public function select(...$columns)
    {
        $this->columns = [];

        foreach ($columns as $column) {
            if (is_string($column))
                $this->addColumn($column);
            elseif (is_array($column) && count($column) == 2)
                $this->addColumn($column[0], $column[1]);
            else
                throw new InvalidArgumentException("Argument should be a string or array of length 2");
        }

        if (!$columns)
            $this->addColumn('*');

        return $this;
    }

    /**
     * Specify the table from which to select
     * 
     * @param string $table the table name
     * @param string $alias (optional) the alias to give to the table
     * @return $this
     * @throws InvalidArgumentException if the table name is not a
     */
    public function from($table, $alias = null)
    {
        if (!is_string($table))
            throw new InvalidArgumentException('Table name should be a string');
        elseif (is_string($alias))
            $table = "$table AS $alias";

        $this->table = $table;

        return $this;
    }

    /**
     * Add a select condition (WHERE clause)
     * 
     * @param string $condition the condition
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string
     */
    public function where(...$conditions)
    {
        foreach ($conditions as $condition) {
            if (!is_string($condition))
                throw new InvalidArgumentException('Conditions must be strings');
        }

        if ($conditions)
            $this->conditions[] = implode(' AND ', $conditions);

        return $this;
    }

    /**
     * Add OR operator for WHERE clause.
     * 
     * @param array|string ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string
     */
    public function orWhere(...$conditions)
    {
        $this->where(...$conditions);
        return $this;
    }

    /**
     * Add ORDER clause to the query
     * 
     * @param string $column the column to select
     * @param string $direction (optional) the direction (ASC or DESC)
     * @return $this
     * @throws InvalidArgumentException if the direction is invalid
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = mb_strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC']))
            throw new InvalidArgumentException('Direction should be either ASC or DESC');

        $this->order[] = "$column $direction";

        return $this;
    }

    public function limit()
    {
    }

    /**
     * Add a column to the SELECT clause
     * 
     * @param string $columnName the name of the column to add
     * @param string $alias (optional) the alias to give to the column
     * @throws InvalidArgumentException if the name/alias is not valid
     */
    private function addColumn($columnName, $alias = null)
    {
        if (is_string($alias))
            $columnName = "$columnName AS $alias";

        $this->columns[] = $columnName;
    }

    protected function validate()
    {
        $conditions = [
            $this->table != null,
            count($this->columns) > 0
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid/incomplete');
    }

    public function toSQL(): string
    {
        $this->validate();

        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM {$this->table}";

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        if ($this->order)
            $sql .= " ORDER BY " . implode(', ', $this->order);

        return $sql;

        // if ($this->limit)
        //     $sql .= " LIMIT {$this->limit}";

        // if ($this->offset)
        //     $sql .= " OFFSET {$this->offset}";

        // return $sql;
    }
}
