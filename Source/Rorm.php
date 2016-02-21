<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */

namespace Rorm;

use PDO;

/**
 * Class Rorm
 * @package Rorm
 */
class Rorm
{
    const CONNECTION_DEFAULT = 'default';

    /** @var PDO[] */
    protected static $connections;

    /**
     * @param PDO $dbh
     * @param string $connection
     */
    public static function setDatabase(PDO $dbh, $connection = self::CONNECTION_DEFAULT)
    {
        static::$connections[$connection] = $dbh;
    }

    /**
     * @param string $connection
     * @return PDO
     * @throws Exception
     */
    public static function getDatabase($connection = self::CONNECTION_DEFAULT)
    {
        if (array_key_exists($connection, static::$connections)) {
            return static::$connections[$connection];
        }

        throw new Exception('Database connection not found!');
    }

    /**
     * @param PDO $dbh
     * @return bool
     */
    public static function isMySQL(PDO $dbh)
    {
        return $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql';
    }

    /**
     * @param PDO $dbh
     * @return bool
     */
    public static function isSQLite(PDO $dbh)
    {
        return $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite';
    }

    /**
     * @param PDO $dbh
     * @param mixed $value
     * @return string|integer|double
     */
    public static function quote(PDO $dbh, $value)
    {
        if ($value === true) {
            /**
             * MySQL has true and false literals
             * SQLite does not support boolean type nor literals
             */
            return static::isMySQL($dbh) ? 'TRUE' : 1;
        } elseif ($value === false) {
            return static::isMySQL($dbh) ? 'FALSE' : 0;
        } elseif ($value === null) {
            return 'NULL';
        } elseif (is_int($value)) {
            return (int)$value;
        } elseif (is_float($value)) {
            return (float)$value;
        }
        return $dbh->quote($value);
    }

    /**
     * Method to quote identifiers
     * Please make sure you keep the quoter as long you are needing it.
     *
     * @param \PDO|null $dbh
     * @return \Closure
     */
    public static function getIdentifierQuoter(PDO $dbh = null)
    {
        $dbh = $dbh ? $dbh : static::getDatabase();

        if (static::isMySQL($dbh)) {
            // mysql mode
            return function ($identifier) {
                return '`' . str_replace('`', '``', $identifier) . '`';
            };
        } else {
            // standard sql mode
            return function ($identifier) {
                return '"' . str_replace('"', '""', $identifier) . '"';
            };
        }
    }
}
