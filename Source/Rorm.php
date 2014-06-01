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
    /** @var PDO */
    public static $db;

    /**
     * @param PDO $dbh
     */
    public static function setDatabase(PDO $dbh)
    {
        self::$db = $dbh;
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
