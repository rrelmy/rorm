<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

class QueryBuilder extends Query
{
    /** @var callable */
    protected $identifierQuoter;

    /** @var string */
    protected $table;

    /** @var array */
    protected $idColumn;

    // query
    protected $distinct = false;

    /** @var array */
    protected $select = [];

    /** @var array */
    protected $where = [];

    /** @var array */
    protected $buildParams = [];

    /** @var array */
    protected $order = [];

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    public function __construct(string $table, $idColumn, string $class = \stdClass::class, \PDO $dbh = null)
    {
        parent::__construct($dbh, $class);

        $this->table = $table;
        $this->idColumn = (array)$idColumn;
        $this->identifierQuoter = Rorm::getIdentifierQuoter($this->connection);
    }

    public function quoteIdentifier(string $identifier): string
    {
        return ($this->identifierQuoter)($identifier);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    // select
    public function distinct(): QueryBuilder
    {
        $this->distinct = true;
        return $this;
    }

    public function selectAll(): QueryBuilder
    {
        $this->select[] = '*';
        return $this;
    }

    public function select(string $column, string $as = null): QueryBuilder
    {
        return $this->selectExpr($this->quoteIdentifier($column), $as);
    }

    public function selectExpr(string $expression, string $as = null): QueryBuilder
    {
        $select = $expression;
        if ($as !== null) {
            $select .= ' AS ' . $this->quoteIdentifier($as);
        }
        $this->select[] = $select;

        return $this;
    }


    // where
    public function where(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' = ?';
        $this->buildParams[] = $value;
        return $this;
    }

    public function whereNot(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' != ?';
        $this->buildParams[] = $value;
        return $this;
    }

    /**
     * @throws QueryBuilderException
     */
    public function whereId($id): QueryBuilder
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
     * TODO could be extended with optional $params
     */
    public function whereExpr(string $column, string $expression): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' = ' . $expression;
        return $this;
    }

    public function whereRaw(string $where, array $params = []): QueryBuilder
    {
        $this->where[] = $where;
        foreach ($params as $param) {
            $this->buildParams[] = $param;
        }
        return $this;
    }

    public function whereLt(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' < ?';
        $this->buildParams[] = $value;
        return $this;
    }

    public function whereLte(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' <= ?';
        $this->buildParams[] = $value;
        return $this;
    }

    public function whereGt(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' > ?';
        $this->buildParams[] = $value;
        return $this;
    }

    public function whereGte(string $column, $value): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' >= ?';
        $this->buildParams[] = $value;
        return $this;
    }

    public function whereNotNull(string $column): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' IS NOT NULL';
        return $this;
    }

    public function whereNull(string $column): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' IS NULL';
        return $this;
    }

    public function whereIn(string $column, array $data): QueryBuilder
    {
        $this->where[] = $this->quoteIdentifier($column) . ' IN (' .
            substr(str_repeat('?, ', count($data)), 0, -2) .
            ')';
        $this->buildParams = array_merge($this->buildParams, $data);
        return $this;
    }

    // order by
    public function orderByAsc(string $column): QueryBuilder
    {
        $this->order[] = $this->quoteIdentifier($column) . ' ASC';
        return $this;
    }

    public function orderByDesc(string $column): QueryBuilder
    {
        $this->order[] = $this->quoteIdentifier($column) . ' DESC';
        return $this;
    }

    public function orderByExpr(string $expression, array $params = []): QueryBuilder
    {
        $this->order[] = $expression;
        $this->buildParams = array_merge($this->buildParams, $params);
        return $this;
    }

    // limit
    public function limit(int $limit): QueryBuilder
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): QueryBuilder
    {
        $this->offset = $offset;
        return $this;
    }

    // execute
    public function build(): QueryBuilder
    {
        $params = [];
        $query = 'SELECT ';

        if ($this->distinct) {
            $query .= 'DISTINCT ';
        }

        // select
        if (!empty($this->select)) {
            $query .= implode(', ', $this->select);
        } else {
            // select everything
            $query .= '*';
        }

        // from
        $query .= ' FROM ' . $this->quoteIdentifier($this->table);

        // where
        if (!empty($this->where)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);

            // params (CAUTION, we override the array, faster and not used before!)
            $params = $this->buildParams;
        }

        // order
        if (!empty($this->order)) {
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

    public function findColumn()
    {
        $this->limit(1);
        $this->build();
        return parent::findColumn();
    }

    public function findOne()
    {
        $this->limit(1);
        $this->build();
        return parent::findOne();
    }

    public function findMany()
    {
        $this->build();
        return parent::findMany();
    }

    public function findAll(): array
    {
        $this->build();
        return parent::findAll();
    }

    /**
     * Count found rows
     * this method executes a COUNT(*) query
     */
    public function count(): int
    {
        $select = $this->select;
        $this->select = ['COUNT(*)'];
        $count = $this->findColumn();
        $this->select = $select;

        return (int)$count;
    }
}
