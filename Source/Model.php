<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */

namespace Rorm;

use stdClass;
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
    public static $_ignoreFields = array();

    /** @var array */
    public $_data = array();

    /**
     * @return string
     */
    public static function getTable()
    {
        if (isset(static::$_table)) {
            return static::$_table;
        }

        return strtolower(str_replace('\\', '_', get_called_class()));
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param mixed $id , ...
     * @return static
     */
    public static function find($id)
    {
        $query = static::query();
        call_user_func_array(array($query, 'whereId'), func_get_args());
        return $query->findOne();
    }

    /**
     * @return QueryBuilder
     */
    public static function query()
    {
        return new QueryBuilder(static::getTable(), static::$_idColumn, get_called_class());
    }

    /**
     * @param string $query
     * @param array $params
     * @return Query
     */
    public static function customQuery($query, array $params = array())
    {
        $ormQuery = new Query(get_called_class());
        $ormQuery->setQuery($query);
        if ($params) {
            $ormQuery->setParams($params);
        }
        return $ormQuery;
    }

    /**
     * @return array|mixed
     */
    public function getId()
    {
        if (is_array(static::$_idColumn)) {
            $result = array();
            foreach (static::$_idColumn as $key) {
                $result[$key] = $this->get($key);
            }
            return $result;
        } else {
            return $this->get(static::$_idColumn);
        }
    }

    /**
     * @return bool
     * @throws QueryException
     */
    public function save()
    {
        if (empty($this->_data)) {
            throw new QueryException('can not save empty data!');
        }

        // ignore fields
        $notSetFields = static::$_ignoreFields;

        // prepare query
        // MySQL and SQLite support REPLACE INTO
        // In SQLite REPLACE INTO is a alias for INSERT INTO OR REPLACE
        $sql = 'REPLACE INTO';
        $sql .= ' ' . Rorm::quoteIdentifier(static::getTable());

        // (column) VALUES (value)
        $columns = array();
        $values = array();

        foreach ($this->_data as $fieldName => $value) {
            if (in_array($fieldName, $notSetFields)) {
                continue;
            }
            $columns[] = Rorm::quoteIdentifier($fieldName);
            $values[] = Rorm::$db->quote($value);
        }

        $sql .= '(' . implode(',', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        unset($columns, $values);

        // execute (most likely throws PDOException if there is an error)
        if (!Rorm::$db->exec($sql)) {
            // @codeCoverageIgnoreStart
            // ignore cover coverage because there should be no way to trigger this error (error mode exception)
            return false;
            // @codeCoverageIgnoreEnd
        }

        // update generated id
        if (static::$_autoId && $this->getId() === null) {
            // last insert id
            $this->set(static::$_idColumn, Rorm::$db->lastInsertId());
        }

        return true;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $sql = '
			DELETE FROM ' . Rorm::quoteIdentifier(static::getTable()) . '
			WHERE
				1
		'; // the 1 has it's purpose

        $idColumns = static::$_idColumn;
        if (!is_array($idColumns)) {
            $idColumns = array($idColumns);
        }
        foreach ($idColumns as $columnName) {
            $sql .= ' AND ' . Rorm::quoteIdentifier($columnName) . ' = ' . Rorm::$db->quote($this->$columnName);
        }

        return Rorm::$db->exec($sql) > 0;
    }

    // data access
    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->_data = $data;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * Remove data from the model
     *
     * ATTENTION:
     * with the goal to only set the data we have this method can lead to unexpected behaviour
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->_data[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * @param stdClass|array|Iterator $object
     * @param array $except
     */
    public function copyDataFrom($object, $except = array())
    {
        foreach ($object as $key => $value) {
            if (!in_array($key, $except)) {
                $this->set($key, $value);
            }
        }
    }

    // Iterator
    public function rewind()
    {
        reset($this->_data);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->_data);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->_data);
    }

    public function next()
    {
        next($this->_data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return key($this->_data) !== null;
    }

    // JsonSerializable
    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->_data;
    }
}
