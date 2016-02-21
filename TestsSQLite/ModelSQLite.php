<?php
/**
 * @author: remy
 */
namespace RormTest;

use Rorm\Model;

/**
 * @property int $rowid
 * @property string $name
 * @property string $email
 * @property float $number
 * @property bool $active
 * @property bool $deleted
 */
class ModelSQLite extends Model
{
    /** @var string */
    public static $_table = 'modelsqlite';

    /** @var string */
    public static $_idColumn = 'rowid';

    /** @var string */
    public static $_connection = 'sqlite';
}
