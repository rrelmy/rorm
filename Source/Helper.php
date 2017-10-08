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
        switch (true) {
            case is_bool($value):
                if ($this->isMySQL($dbh)) {
                    return $value ? 'TRUE' : 'FALSE';
                }
                return $value ? 1 : 0;
            case $value === null:
                return 'NULL';
            case is_int($value):
                return (int)$value;
            case is_float($value):
                return (float)$value;
            default:
                return $dbh->quote($value);
        }
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
