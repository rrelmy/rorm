<?php
/**
 * @author: remy
 */
namespace RormTest;

use Rorm\Model;

/**
 * @property int $id
 * @property string $name
 * @property float $number
 * @property bool $active
 * @property bool $deleted
 */
class ModelPostgreSQL extends Model
{
    /** @var string */
    public static $_table = 'test_basic';

    /** @var string */
    public static $_connection = 'pgsql';
}
