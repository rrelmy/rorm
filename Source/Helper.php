<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

class Helper
{
    public function isMySQL(\PDO $dbh): bool
    {
        return $dbh->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    public function isSQLite(\PDO $dbh): bool
    {
        return $dbh->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public function quote(\PDO $dbh, $value)
    {
        if (is_bool($value)) {
            /**
             * MySQL has true and false literals
             * SQLite does not support boolean type nor literals
             */
            if ($this->isMySQL($dbh)) {
                return $value ? 'TRUE' : 'FALSE';
            }

            return $value ? 1 : 0;
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
    public function getIdentifierQuoter(\PDO $dbh): callable
    {
        if ($this->isMySQL($dbh)) {
            // mysql mode
            return function ($identifier) {
                return '`' . str_replace('`', '``', $identifier) . '`';
            };
        }

        // standard sql mode
        return function ($identifier) {
            return '"' . str_replace('"', '""', $identifier) . '"';
        };
    }
}
