<?php
/**
 * @author: remy
 */
namespace RormTest;

use Rorm\Model;

/**
 * @property int $foo_id
 * @property int $bar_id
 * @property string $name
 * @property int $rank
 */
class ModelPostgreSQLCompound extends Model
{
    public static $_table = 'test_compound';

    /** @var string|array */
    public static $_idColumn = array('foo_id', 'bar_id');

    /** @var bool */
    public static $_autoId = false;

    /** @var string */
    public static $_connection = 'pgsql';
}
