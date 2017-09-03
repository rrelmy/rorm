<?php
/**
 * @author: remy
 */

namespace RormTest\Model;

use Rorm\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property float $number
 * @property string $modified
 * @property bool $active
 * @property bool $deleted
 */
class TestBasic extends Model
{
    public static $_table = 'test_basic';

    /** @var array */
    public static $_ignoreColumns = array('modified');
}
