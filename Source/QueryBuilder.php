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
    /** @var callable */
    protected $quoteIdentifier;

    /** @var string */
    protected $table;

    /** @var array */
    protected $idColumn;

    // query
    protected $distinct = false;

    /** @var array */
    protected $select = array();

    /** @var array */
    protected $where = array();

    /** @var array */
    protected $whereParams = array();

    /** @var array */
    protected $order = array();

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /**
     * @param string $table
     * @param string|array $idColumn
     * @param string $class
     * @param \PDO $db
     */
    public function __construct($table, $idColumn, $class = 'stdClass', \PDO $db = null)
    {
        parent::__construct($class, $db);

        $this->table = $table;
        $this->idColumn = is_array($idColumn) ? $idColumn : array($idColumn);
        $this->quoteIdentifier = Rorm::getIdentifierQuoter($this->db);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        // TODO there must be an easier way to do this without an extra variable!
        $func = $this->quoteIdentifier;
        return $func($identifier);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    // select
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function selectAll()
    {
        $this->select[] = '*';
        return $this;
    }

    /**
     * @param string $column
     * @param string $as
     * @return $this
     */
    public function select($column, $as = null)
    {
        $select = $this->quoteIdentifier($column);
        if ($as !== null) {
            $select .= ' AS ' . $this->quoteIdentifier($as);
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
            $select .= ' AS ' . $this->quoteIdentifier($as);
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
        $this->where[] = $this->quoteIdentifier($column) . ' = ?';
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
     * check: could be extended with optional $params
     *
     * @param string $column
     * @param string $expression
     * @return $this
     */
    public function whereExpr($column, $expression)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' = ' . $expression;
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
     * Take care, the $values gets quoted!
     *
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereLt($column, $value)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' < ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * Take care, the $values gets quoted!
     *
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereLte($column, $value)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' <= ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * Take care, the $values gets quoted!
     *
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereGt($column, $value)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' > ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * Take care, the $values gets quoted!
     *
     * @param string $column
     * @param int|float|string $value
     * @return $this
     */
    public function whereGte($column, $value)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' >= ?';
        $this->whereParams[] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' IS NOT NULL';
        return $this;
    }

    /**
     * @param string $column
     * @param array $data
     * @return $this
     */
    public function whereIn($column, array $data)
    {
        $this->where[] = $this->quoteIdentifier($column) . ' IN (' .
            substr(str_repeat('?, ', count($data)), 0, -2) .
            ')';
        $this->whereParams = array_merge($this->whereParams, $data);
        return $this;
    }

    // order by
    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc($column)
    {
        $this->order[] = $this->quoteIdentifier($column) . ' ASC';
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        $this->order[] = $this->quoteIdentifier($column) . ' DESC';
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
        $query = 'SELECT ';

        if ($this->distinct) {
            $query .= 'DISTINCT ';
        }

        // select
        if ($this->select) {
            $query .= implode(', ', $this->select);
        } else {
            // select everything
            $query .= '*';
        }

        // from
        $query .= ' FROM ' . $this->quoteIdentifier($this->table);

        // where
        if ($this->where) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);

            // params (CAUTION, we override the array, faster and not used before!)
            $params = $this->whereParams;
        }

        // order
        if ($this->order) {
            $query .= ' ORDER BY ' . implode(', ', $this->order);
        }

        // limit
        if ($this->limit !== null) {
            $query .= ' LIMIT ' . (int)$this->limit;

            // offset
            if ($this->offset !== null) {
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
    public function findColumn()
    {
        $this->build();
        return parent::findColumn();
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

    /**
     * Count found rows
     * this method executes a COUNT(*) query
     *
     * @return int
     */
    public function count()
    {
        $select = $this->select;
        $this->select = array('COUNT(*)');
        $count = $this->findColumn();
        $this->select = $select;

        return $count === null ? null : (int) $count;
    }
}
