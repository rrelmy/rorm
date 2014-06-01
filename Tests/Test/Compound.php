<?php
/**
 * @author: remy
 */
namespace RormTest\Test;

use Rorm\Model;

/**
 * @property int $foo_id
 * @property int $bar_id
 * @property string $name
 * @property int $rank
 */
class Compound extends Model
{
    /** @var string|array */
    public static $_idColumn = array('foo_id', 'bar_id');

    /** @var bool */
    public static $_autoId = false;
}
