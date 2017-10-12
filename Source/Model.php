<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use Iterator;
use JsonSerializable;

abstract class Model implements Iterator, JsonSerializable
{
    /** @var string|null */
    protected $_table;

    /** @var string|array */
    protected $_idColumn = 'id';

    /** @var bool */
    protected $_autoId = true;

    /** @var array */
    protected $_ignoreColumns = [];

    /** @var string */
    protected $_connection;

    /** @var array */
    protected $_data = [];

    /** @var ConnectionResolver */
    protected static $connectionResolver;

    public static function setConnectionResolver(ConnectionResolver $resolver): void
    {
        static::$connectionResolver = $resolver;
    }

    public static function unsetConnectionResolver(): void
    {
        static::$connectionResolver = null;
    }

    public function getTable(): string
    {
        if ($this->_table !== null) {
            return $this->_table;
        }

        return strtolower(str_replace('\\', '_', static::class));
    }

    public function getConnection(): \PDO
    {
        return static::$connectionResolver->connection($this->_connection);
    }

    /**
     * @return static
     */
    public static function find($id): ?Model
    {
        $query = (new static)->query();
        $query->whereId(...func_get_args());

        return $query->findOne();
    }

    /**
     * @return static[]
     */
    public static function findAll(): array
    {
        return (new static)->query()->findAll();
    }

    public function query(): QueryBuilder
    {
        // maybe pass $this?
        return new QueryBuilder(
            $this->getConnection(),
            new ModelBuilder(),
            static::class,
            new Helper(),
            $this->getTable(),
            $this->_idColumn
        );
    }

    public function customQuery(string $query, array $params = []): Query
    {
        $model = new static;
        $ormQuery = new Query($model->getConnection(), new ModelBuilder(), static::class);
        $ormQuery->setQuery($query);
        if (!empty($params)) {
            $ormQuery->setParams($params);
        }
        return $ormQuery;
    }

    public function getId()
    {
        if (is_array($this->_idColumn)) {
            $result = [];
            /** @var string[] $columns */
            $columns = $this->_idColumn;
            foreach ($columns as $key) {
                $result[$key] = $this->get($key);
            }
            return $result;
        }

        return $this->get($this->_idColumn);
    }

    public function hasId(): bool
    {
        if (is_array($this->_idColumn)) {
            /** @var string[] $columns */
            $columns = $this->_idColumn;
            foreach ($columns as $key) {
                $value = $this->get($key);
                if (empty($value)) {
                    return false;
                }
            }
            return true;
        }

        $value = $this->get($this->_idColumn);
        return !empty($value);
    }

    /**
     * @throws QueryException
     * @throws \PDOException
     */
    public function save(): bool
    {
        $dbh = $this->getConnection();
        $helper = new Helper();
        $quoteIdentifier = $helper->getIdentifierQuoter($dbh);
        $quotedTable = $quoteIdentifier($this->getTable());

        $idColumns = (array)$this->_idColumn;
        $doMerge = $this->hasId();

        // ignore fields
        $notSetFields = $this->_ignoreColumns;

        /**
         * Different queries are built for each driver
         *
         * IDEA: probably split into methods (saveMySQL, saveSQLite)
         */
        if ($helper->isMySQL($dbh)) {
            /**
             * MySQL
             * Instead of REPLACE INTO we use INSERT INTO ON DUPLICATE KEY UPDATE.
             * Because REPLACE INTO does DELETE and INSERT,
             * which does not play nice with TRIGGERs and FOREIGN KEY CONSTRAINTS
             */
            $sql = 'INSERT INTO ' . $quotedTable . ' ';

            $insertData = [];
            $updateData = [];

            foreach ($this->_data as $column => $value) {
                if (in_array($column, $notSetFields, true)) {
                    continue;
                }

                $quotedColumn = $quoteIdentifier($column);
                $insertData[$quotedColumn] = $helper->quote($dbh, $value);

                if ($doMerge && !in_array($column, $idColumns, true)) {
                    $updateData[] = $quotedColumn . ' = VALUES(' . $quotedColumn . ')';
                }
            }
            unset($column, $value, $quotedColumn);

            // insert
            $sql .=
                '(' . implode(', ', array_keys($insertData)) . ')' .
                ' VALUES ' .
                '(' . implode(', ', $insertData) . ')';

            if ($doMerge && count($updateData) > 0) {
                // update
                $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateData);
            }

            // execute (most likely throws PDOException if there is an error)
            if ($dbh->exec($sql) === false) {
                return false; // @codeCoverageIgnore
            }

            // update generated id
            if ($this->_autoId && !$doMerge) {
                // last insert id
                $this->set($this->_idColumn, $dbh->lastInsertId());
            }

            return true;
        }

        /**
         * SQLite
         */
        if ($doMerge) {
            $sql = 'INSERT OR REPLACE INTO ' . $quotedTable . ' ';
        } else {
            $sql = 'INSERT INTO ' . $quotedTable . ' ';
        }

        // build (column) VALUES (values)
        $quotedData = [];
        foreach ($this->_data as $column => $value) {
            if (in_array($column, $notSetFields, true)) {
                continue;
            }

            $quotedData[$quoteIdentifier($column)] = $helper->quote($dbh, $value);
        }
        unset($column, $value);

        $sql .= '(' . implode(', ', array_keys($quotedData)) . ') VALUES (' . implode(', ', $quotedData) . ')';

        // execute (most likely throws PDOException if there is an error)
        if ($dbh->exec($sql) === false) {
            return false; // @codeCoverageIgnore
        }

        // update generated id
        if ($this->_autoId && !$this->hasId()) {
            // last insert id
            $this->set($this->_idColumn, $dbh->lastInsertId());
        }

        return true;
    }

    public function delete(): bool
    {
        $dbh = $this->getConnection();
        $helper = new Helper();
        $quoteIdentifier = $helper->getIdentifierQuoter($dbh);

        $idColumns = (array)$this->_idColumn;

        $where = [];
        foreach ($idColumns as $columnName) {
            $where[] = $quoteIdentifier($columnName) . ' = ' . $helper->quote($dbh, $this->$columnName);
        }

        $sql = 'DELETE FROM ' . $quoteIdentifier($this->getTable()) . ' WHERE ' . implode(' AND ', $where);

        return $dbh->exec($sql) > 0;
    }

    // data access
    public function getData(): array
    {
        return $this->_data;
    }

    public function setData(array $data): void
    {
        $this->_data = $data;
    }

    public function get(string $name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        return null;
    }

    public function set(string $name, $value): Model
    {
        $this->_data[$name] = $value;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->_data[$name]);
    }

    /**
     * Remove data from the model
     */
    public function remove(string $name): void
    {
        $this->_data[$name] = null;
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __unset(string $name): void
    {
        $this->remove($name);
    }

    public function copyDataFrom($object, array $except = []): void
    {
        foreach ($object as $key => $value) {
            if (!in_array($key, $except, true)) {
                $this->set($key, $value);
            }
        }
    }

    // Iterator
    public function rewind(): void
    {
        reset($this->_data);
    }

    public function current()
    {
        return current($this->_data);
    }

    public function key()
    {
        return key($this->_data);
    }

    public function next(): void
    {
        next($this->_data);
    }

    public function valid(): bool
    {
        return key($this->_data) !== null;
    }

    // JsonSerializable
    public function jsonSerialize()
    {
        return $this->_data;
    }
}
