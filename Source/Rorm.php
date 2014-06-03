<?php
/**
 *
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
        self::$connections[$connection] = $dbh;
    }

    /**
     * @param string $connection
     * @return PDO|null
     */
    public static function getDatabase($connection = self::CONNECTION_DEFAULT)
    {
        if (array_key_exists($connection, self::$connections)) {
            return self::$connections[$connection];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return string
     *
     * @todo this method uses mysql back ticks, but should be compatible with other sql modes
     */
    public static function quoteIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
