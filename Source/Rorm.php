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

    /** @var array */
    protected static $supportedDrivers = array('mysql', 'sqlite', 'pgsql');

    /** @var PDO[] */
    protected static $connections;

    /**
     * @param PDO $dbh
     * @param string $connection
     * @throws Exception
     */
    public static function setDatabase(PDO $dbh, $connection = self::CONNECTION_DEFAULT)
    {
        $driverName = $dbh->getAttribute(PDO::ATTR_DRIVER_NAME);

        if (!in_array($driverName, self::$supportedDrivers)) {
            throw new Exception('database driver «' . $driverName . '» is not supported');
        }

        // FIXME find better way than dynamic fields
        // new RormPDOWrapper($dbh)?
        $dbh->isMySQL = $driverName == 'mysql';
        $dbh->isSQLite = !$dbh->isMySQL && $driverName == 'sqlite';
        $dbh->isPostgreSQL = !$dbh->isMySQL && !$dbh->isSQLite && $driverName == 'pgsql';

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
     * @param PDO $db
     * @param mixed $value
     * @return mixed
     */
    public static function quote(PDO $db, $value)
    {
        switch (gettype($value)) {
            case 'boolean':
                return $value ? 'TRUE' : 'FALSE';
            case 'NULL':
                return 'NULL';
            case 'integer':
                return (int)$value;
            case 'double':
                return (float)$value;
        }
        
        return $db->quote($value);
    }

    /**
     * Method to quote identifier
     * Please make sure you keep the quoter as long you are needing it for performance reasons.
     *
     * @param \PDO $dbh
     * @return callable
     */
    public static function getIdentifierQuoter(\PDO $dbh = null)
    {
        $dbh = $dbh ? $dbh : self::getDatabase();

        if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
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
