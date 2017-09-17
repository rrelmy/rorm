<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

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
     * @throws ConnectionNotFoundException
     */
    public static function getDatabase(string $connection = self::CONNECTION_DEFAULT): PDO
    {
        if (array_key_exists($connection, static::$connections)) {
            return static::$connections[$connection];
        }

        throw new ConnectionNotFoundException('Database connection not found!');
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
        if (is_bool($value)) {
            /**
             * MySQL has true and false literals
             * SQLite does not support boolean type nor literals
             */
            if (static::isMySQL($dbh)) {
                return $value ? 'TRUE' : 'FALSE';
            } else {
                return $value ? 1 : 0;
            }
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value)) {
            return (int)$value;
        }
        if (is_float($value)) {
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

    public static function reset()
    {
        static::$connections = [];
    }
}
