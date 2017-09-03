<?php
/**
 * @author: remy
 */
declare(strict_types=1);

namespace RormTest;

use Rorm\Model;

/**
 * Class ModelSQLiteCompound
 * @package RormTest
 *
 * @property int $foo_id
 * @property int $bar_id
 * @property string $name
 * @property int $rank
 */
class ModelSQLiteCompound extends Model
{
    public static $_table = 'modelsqlitecompound';

    /** @var string|array */
    public static $_idColumn = ['foo_id', 'bar_id'];

    /** @var bool */
    public static $_autoId = false;

    /** @var string */
    public static $_connection = 'sqlite';
}
