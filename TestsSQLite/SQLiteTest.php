<?php
namespace RormTest;

use Exception;
use PDO;
use PHPUnit_Framework_TestCase;
use Rorm\Rorm;

/**
 * @author: remy
 */
class SQLiteTest extends PHPUnit_Framework_TestCase
{
    protected static $backupDb;

    public static function setUpBeforeClass()
    {
        // backup database
        self::$backupDb = Rorm::$db;

        // create sqlite database
        $dbh = new PDO('sqlite::memory:');
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Rorm::setDatabase($dbh);

        // setup database
        $dbh->exec('DROP TABLE IF EXISTS modelsqlite');
        $dbh->exec(
            'CREATE TABLE modelsqlite (
                 rowid INTEGER PRIMARY KEY AUTOINCREMENT,
                 name TEXT NOT NULL,
                 number REAL,
                 active INTEGER,
                 deleted INTEGER
            );'
        );
        $dbh->exec('DROP TABLE IF EXISTS modelsqlitecompound');
        $dbh->exec(
            'CREATE TABLE modelsqlitecompound (
                foo_id INTEGER,
                bar_id INTEGER,
                name TEST,
                rank INTEGER,
                PRIMARY KEY(foo_id, bar_id)
            );'
        );
    }

    public static function tearDownAfterClass()
    {
        // restore database
        Rorm::setDatabase(self::$backupDb);
    }


    public function testBasic()
    {
        $model = ModelSQLite::create();
        $this->assertInstanceOf('\\RormTest\\ModelSQLite', $model);

        $model->name = 'Lorem';
        $model->number = 10.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        $this->assertNotEmpty($model->rowid);

        // load
        $modelLoaded = ModelSQLite::find($model->rowid);
        $this->assertNotEmpty($modelLoaded);
        $this->assertInstanceOf('\\RormTest\\ModelSQLite', $modelLoaded);

        $this->assertEquals($model->name, $modelLoaded->name);
        $this->assertEquals($model->number, $modelLoaded->number);

        // update
        $model->name = 'Lorem ipsum';
        $this->assertTrue($model->save());

        // re load
        $modelLoaded = ModelSQLite::find($model->rowid);
        $this->assertEquals($model->name, $modelLoaded->name);

        // delete
        $this->assertTrue($model->delete());

        // re load empty
        $this->assertNull(ModelSQLite::find($model->rowid));
    }

    /**
     * @depends testBasic
     */
    public function testCompound()
    {
        // check if empty
        $result = ModelSQLiteCompound::query()->findAll();
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);

        // create
        $model1 = ModelSQLiteCompound::create();
        $model1->foo_id = 5;
        $model1->bar_id = 10;
        $model1->name = '5 to 10';
        $this->assertTrue($model1->save());

        // create
        $model2 = ModelSQLiteCompound::create();
        $model2->foo_id = 7;
        $model2->bar_id = 10;
        $model2->name = '7 to 10';
        $this->assertTrue($model2->save());

        // create
        $model3 = ModelSQLiteCompound::create();
        $model3->foo_id = 11;
        $model3->bar_id = 1;
        $model3->name = '11 to 1';
        $this->assertTrue($model3->save());


        // create and delete
        $model4 = ModelSQLiteCompound::create();
        $model4->foo_id = 11;
        $model4->bar_id = 8;
        $model4->name = '11 to 8';
        $this->assertTrue($model4->save());
        $this->assertTrue($model4->delete());

        // query one
        $model = ModelSQLiteCompound::find(5, 10);
        $this->assertInstanceOf('\\RormTest\\ModelSQLiteCompound', $model);
        $this->assertEquals(5, $model->foo_id);
        $this->assertEquals(10, $model->bar_id);

        // query many
        $query = ModelSQLiteCompound::query();
        $query->whereGt('foo_id', 6);
        $query->orderByAsc('foo_id');
        $result = $query->findMany();

        $this->assertInstanceOf('\\Rorm\\QueryIterator', $result);

        foreach ($result as $model) {
            /** @var ModelSQLiteCompound $model */

            // check if correct model
            $this->assertInstanceOf('\\RormTest\\ModelSQLiteCompound', $model);

            // check if not filtered item
            $this->assertNotEquals($model1->foo_id, $model->foo_id);
        }

        // query buffered
        $result = ModelSQLiteCompound::query()->findAll();
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf('\\RormTest\\ModelSQLiteCompound', $result);
        $this->assertEquals(3, count($result));
    }

    /**
     * @depends testCompound
     * @expectedException Exception
     */
    public function testQueryRewind()
    {
        $result = ModelSQLiteCompound::query()->findMany();
        $this->assertNotEmpty($result);

        foreach ($result as $model) {
            $this->assertInstanceOf('\\RormTest\\ModelSQLiteCompound', $model);
        }

        // here the exception should get thrown
        foreach ($result as $model) {
            $this->assertInstanceOf('\\RormTest\\ModelSQLiteCompound', $model);
        }
    }
}
