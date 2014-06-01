<?php
/**
 * @author: remy
 */
namespace RormTest;

use Rorm\Model;

/**
 * @property int $rowid
 * @property string $name
 * @property float $number
 * @property bool $active
 * @property bool $deleted
 */
class ModelSQLite extends Model
{
    public static $_table = 'modelsqlite';
    public static $_idColumn = 'rowid';
}
