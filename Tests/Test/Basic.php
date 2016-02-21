<?php
/**
 * @author: remy
 */
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
class Test_Basic extends Model
{
    /** @var array */
    public static $_ignoreColumns = array('modified');
}
