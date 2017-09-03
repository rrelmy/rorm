<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use Iterator;
use JsonSerializable;

/**
 * Class Model
 */
abstract class Model implements Iterator, JsonSerializable
{
    /** @var string */
    public static $_table;

    /** @var string|array */
    public static $_idColumn = 'id';

    /** @var bool */
    public static $_autoId = true;

    /** @var array */
    public static $_ignoreColumns = [];

    /** @var string */
    public static $_connection = Rorm::CONNECTION_DEFAULT;

    /** @var array */
    public $_data = [];

    public static function getTable(): string
    {
        if (static::$_table !== null) {
            return static::$_table;
        }

        return strtolower(str_replace('\\', '_', static::class));
    }

    /**
     * @throws \Rorm\Exception
     */
    public static function getDatabase(): \PDO
    {
        return Rorm::getDatabase(static::$_connection);
    }

    /**
     * @return static
     */
    public static function create(): Model
    {
        return new static();
    }

    /**
     * @return static
     */
    public static function find($id): ?Model
    {
        $query = static::query();
        call_user_func_array([$query, 'whereId'], func_get_args());
        return $query->findOne();
    }

    /**
     * @return static[]
     */
    public static function findAll(): array
    {
        return static::query()->findAll();
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::getTable(), static::$_idColumn, static::class, static::getDatabase());
    }

    public static function customQuery(string $query, array $params = []): Query
    {
        $ormQuery = new Query(static::class, static::getDatabase());
        $ormQuery->setQuery($query);
        if (!empty($params)) {
            $ormQuery->setParams($params);
        }
        return $ormQuery;
    }

    public function getId()
    {
        if (is_array(static::$_idColumn)) {
            $result = [];
            foreach (static::$_idColumn as $key) {
                $result[$key] = $this->get($key);
            }
            return $result;
        } else {
            return $this->get(static::$_idColumn);
        }
    }

    public function hasId(): bool
    {
        if (is_array(static::$_idColumn)) {
            foreach (static::$_idColumn as $key) {
                $value = $this->get($key);
                if (empty($value)) {
                    return false;
                }
            }
            return true;
        } else {
            $value = $this->get(static::$_idColumn);
            return !empty($value);
        }
    }

    /**
     * @throws QueryException
     * @throws \PDOException
     */
    public function save(): bool
    {
        if (empty($this->_data)) {
            throw new QueryException('can not save empty data!');
        }

        $dbh = static::getDatabase();
        $quoteIdentifier = Rorm::getIdentifierQuoter($dbh);
        $quotedTable = $quoteIdentifier(static::getTable());

        $idColumns = (array)static::$_idColumn;
        $doMerge = $this->hasId();

        // ignore fields
        $notSetFields = static::$_ignoreColumns;

        /**
         * Different queries are built for each driver
         *
         * IDEA: probably split into methods (saveMySQL, saveSQLite)
         */
        if (Rorm::isMySQL($dbh)) {
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
                if (in_array($column, $notSetFields)) {
                    continue;
                }

                $quotedColumn = $quoteIdentifier($column);
                $insertData[$quotedColumn] = Rorm::quote($dbh, $value);

                if ($doMerge && !in_array($column, $idColumns)) {
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
            if (static::$_autoId && !$doMerge) {
                // last insert id
                $this->set(static::$_idColumn, $dbh->lastInsertId());
            }

            return true;
        } else {
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
                if (in_array($column, $notSetFields)) {
                    continue;
                }

                $quotedData[$quoteIdentifier($column)] = Rorm::quote($dbh, $value);
            }
            unset($column, $value);

            $sql .= '(' . implode(', ', array_keys($quotedData)) . ') VALUES (' . implode(', ', $quotedData) . ')';

            // execute (most likely throws PDOException if there is an error)
            if ($dbh->exec($sql) === false) {
                return false; // @codeCoverageIgnore
            }

            // update generated id
            if (static::$_autoId && !$this->hasId()) {
                // last insert id
                $this->set(static::$_idColumn, $dbh->lastInsertId());
            }

            return true;
        }
    }

    public function delete(): bool
    {
        $dbh = static::getDatabase();
        $quoteIdentifier = Rorm::getIdentifierQuoter($dbh);

        $idColumns = (array)static::$_idColumn;

        $where = [];
        foreach ($idColumns as $columnName) {
            $where[] = $quoteIdentifier($columnName) . ' = ' . Rorm::quote($dbh, $this->$columnName);
        }

        $sql = 'DELETE FROM ' . $quoteIdentifier(static::getTable()) . ' WHERE ' . implode(' AND ', $where);

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
            if (!in_array($key, $except)) {
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
