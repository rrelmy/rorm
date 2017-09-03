<?php
/**
 * @author: remy
 */
declare(strict_types=1);

namespace RormTest;

use Rorm\Model;

/**
 * Class ModelSQLite
 * @package RormTest
 *
 * @property int $rowid
 * @property string $name
 * @property string $email
 * @property float $number
 * @property bool $active
 * @property bool $deleted
 * @property int $ignored_column
 */
class ModelSQLite extends Model
{
    /** @var string */
    public static $_table = 'modelsqlite';

    /** @var string */
    public static $_idColumn = 'rowid';

    /** @var string */
    public static $_connection = 'sqlite';

    /** @var array */
    public static $_ignoreColumns = ['ignored_column'];
}
