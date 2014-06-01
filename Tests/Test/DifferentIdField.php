<?php
namespace RormTest\Test;

use Rorm\Model;

/**
 * @author: remy
 */
class DifferentIdField extends Model
{
    public static $_idColumn = 'custom_id';
}
