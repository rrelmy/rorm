<?php
/**
 * @author: remy
 */
namespace RormTest\Test;

use Rorm\Model;

/**
 * Class QueryModel
 *
 * @property int $id
 * @property string $name
 * @property float $number
 * @property string $modified
 * @property bool $active
 * @property bool $deleted
 */
class QueryModel extends Model
{
    public static $_table = 'test';
}
