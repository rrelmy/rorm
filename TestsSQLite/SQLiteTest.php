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
    public function testDbhFlag()
    {
        $this->assertTrue(Rorm::getDatabase('sqlite')->isSQLite);
    }

    public function testQuote()
    {
        $db = Rorm::getDatabase('sqlite');

        $this->assertEquals(1, Rorm::quote($db, true));
        $this->assertEquals(0, Rorm::quote($db, false));
    }

    public function testModels()
    {
        $sqliteDatabase = Rorm::getDatabase('sqlite');
        $this->assertInstanceOf('PDO', $sqliteDatabase);
        $this->assertEquals($sqliteDatabase->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite');

        $this->assertEquals($sqliteDatabase, ModelSQLite::getDatabase());
        $this->assertEquals($sqliteDatabase, ModelSQLiteCompound::getDatabase());
    }

    /**
     * @depends testModels
     */
    public function testQuoteIdentifier()
    {
        $quoter = Rorm::getIdentifierQuoter(Rorm::getDatabase('sqlite'));
        $this->assertEquals('"sqlite"', $quoter('sqlite'));
    }

    /**
     * @depends testModels
     */
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
    public function testBasicQueryBuilder()
    {
        // create some data
        $model = ModelSQLite::create();
        $model->name = 'QueryBuilder';
        $model->number = 17.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        // query data
        $query = ModelSQLite::query();
        $this->assertInstanceOf('\\Rorm\\QueryBuilder', $query);

        $query
            ->selectAll()
            ->select('deleted', 'deleted2')
            ->selectExpr('number + 10', 'higher_number')
            ->where('active', true)
            ->where('deleted', 0) // FIXME does not accept false!
            ->whereNotNull('name')
            ->whereRaw('name = ?', array($model->name))
            ->whereIn('name', array('Lorem', 'ipsum', 'QueryBuilder'))
            ->whereGt('number', 0)
            ->whereGte('number', 5)
            ->whereLt('number', 90)
            ->whereLte('number', 18)
            ->whereExpr('number', '10.7 + 7.05')
            ->orderByAsc('number')
            ->orderByDesc('id')
            ->limit(1)
            ->offset(0);

        $queryModel = $query->findOne();
        $this->assertInstanceOf('\\RormTest\\ModelSQLite', $queryModel);
        $this->assertEquals($model->getId(), $queryModel->getId());

        // test boolean parameters
        $this->assertTrue((bool)$queryModel->active);
        $this->assertFalse((bool)$queryModel->deleted);
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
