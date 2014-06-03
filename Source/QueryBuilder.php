<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
namespace Rorm;

/**
 * Class QueryBuilder
 * @package Rorm
 */
class QueryBuilder extends Query
{
    /** @var string */
    protected $table;

    /** @var array */
    protected $idColumn;

    // query
    public $select = array();

    public $where = array();
    public $whereParams = array();

    public $order = array();

    /** @var int */
    public $limit;

    /** @var int */
    public $offset;

    /**
     * @param string $table
     * @param string|array $idColumn
     * @param string $class
     * @param \PDO $db
     */
    public function __construct($table, $idColumn, $class = 'stdClass', \PDO $db = null)
    {
        $this->table = $table;
        $this->idColumn = is_array($idColumn) ? $idColumn : array($idColumn);
        parent::__construct($class, $db);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    // select
    /**
     * @param string $column
     * @param string $as
     * @return $this
     */
    public function select($column, $as = null)
    {
        $select = Rorm::quoteIdentifier($column);
        if ($as !== null) {
            $select .= ' AS ' . Rorm::quoteIdentifier($as);
        }
        $this->select[] = $select;

        return $this;
    }

    /**
     * @param string $expression
     * @param string $as
     * @return $this
     */
    public function selectExpr($expression, $as = null)
    {
        $select = $expression;
        if ($as !== null) {
            $select .= ' AS ' . Rorm::quoteIdentifier($as);
        }
        $this->select[] = $select;

        return $this;
    }


    // where
    /**
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function where($column, $value)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' = ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param mixed $id , ...
     * @return $this
     *
     * @throws QueryBuilderException
     */
    public function whereId($id)
    {
        $args = func_get_args();
        if (count($args) !== count($this->idColumn)) {
            throw new QueryBuilderException('number of id parameters must match');
        }

        $keys = array_combine($this->idColumn, $args);
        foreach ($keys as $column => $value) {
            $this->where($column, $value);
        }

        return $this;
    }

    /**
     * @param string $column
     * @param string $expression
     * @return $this
     */
    public function whereExpr($column, $expression)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' = ' . $expression;
        return $this;
    }

    /**
     * @param string $where
     * @param array $params
     * @return $this
     */
    public function whereRaw($where, array $params = array())
    {
        $this->where[] = $where;
        foreach ($params as $param) {
            $this->whereParams[] = $param;
        }
        return $this;
    }

    /**
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereLt($column, $value)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' < ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereLte($column, $value)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' <= ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereGt($column, $value)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' > ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereGte($column, $value)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' >= ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' IS NOT NULL';
        return $this;
    }

    /**
     * @param string $column
     * @param array $data
     * @return $this
     */
    public function whereIn($column, array $data)
    {
        $this->where[] = Rorm::quoteIdentifier($column) . ' IN (' .
            substr(str_repeat('?, ', count($data)), 0, -2) .
            ')';
        $this->whereParams = array_merge($this->whereParams, $data);
        return $this;
    }

    // group

    // having

    // order by
    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc($column)
    {
        $this->order[] = Rorm::quoteIdentifier($column) . ' ASC';
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        $this->order[] = Rorm::quoteIdentifier($column) . ' DESC';
        return $this;
    }

    // limit
    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }


    // execute
    /**
     * @return $this
     */
    public function build()
    {
        $params = array();
        $query = 'SELECT';

        // select
        if ($this->select) {
            $query .= ' ' . implode(', ', $this->select);
        } else {
            // select everything
            $query .= ' *';
        }

        // from
        $query .= ' FROM ' . Rorm::quoteIdentifier($this->table);

        // where
        if ($this->where) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);

            // params
            foreach ($this->whereParams as $param) {
                $params[] = $param;
            }
        }

        // order
        if ($this->order) {
            $query .= ' ORDER BY ' . implode(', ', $this->order);
        }

        // limit
        if ($this->limit) {
            $query .= ' LIMIT ' . (int)$this->limit;

            // offset
            if ($this->offset) {
                $query .= ' OFFSET ' . (int)$this->offset;
            }
        }

        $this->query = $query;
        $this->params = $params;

        return $this;
    }

    /**
     * @return mixed
     */
    public function findOne()
    {
        $this->build();
        return parent::findOne();
    }

    /**
     * @return QueryIterator
     */
    public function findMany()
    {
        $this->build();
        return parent::findMany();
    }

    /**
     * @return array
     */
    public function findAll()
    {
        $this->build();
        return parent::findAll();
    }
}
