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

    public static function setDatabase(PDO $dbh, string $connection = self::CONNECTION_DEFAULT): void
    {
        static::$connections[$connection] = $dbh;
    }

    /**
     * @throws Exception
     */
    public static function getDatabase(string $connection = self::CONNECTION_DEFAULT): PDO
    {
        if (array_key_exists($connection, static::$connections)) {
            return static::$connections[$connection];
        }

        throw new Exception('Database connection not found!');
    }

    public static function isMySQL(PDO $dbh): bool
    {
        return $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    public static function isSQLite(PDO $dbh): bool
    {
        return $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

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
     */
    public static function getIdentifierQuoter(PDO $dbh = null): ?callable
    {
        $dbh = $dbh ?: static::getDatabase();

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
