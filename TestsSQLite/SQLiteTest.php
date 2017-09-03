<?php
/**
 * @author: remy
 */

namespace RormTest;

use Exception;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Rorm\QueryBuilder;
use Rorm\QueryIterator;
use Rorm\Rorm;

/**
 * Class SQLiteTest
 * @package RormTest
 */
class SQLiteTest extends TestCase
{
    public function testDbDriver()
    {
        $dbh = Rorm::getDatabase('sqlite');
        $this->assertTrue(Rorm::isSQLite($dbh));
        $this->assertFalse(Rorm::isMySQL($dbh));
    }

    public function testQuote()
    {
        $dbh = Rorm::getDatabase('sqlite');

        $this->assertEquals(1, Rorm::quote($dbh, true));
        $this->assertEquals(0, Rorm::quote($dbh, false));
    }

    public function testModels()
    {
        $dbh = Rorm::getDatabase('sqlite');
        $this->assertInstanceOf('PDO', $dbh);
        $this->assertEquals($dbh->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite');

        $this->assertEquals($dbh, ModelSQLite::getDatabase());
        $this->assertEquals($dbh, ModelSQLiteCompound::getDatabase());
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
        $this->assertInstanceOf(ModelSQLite::class, $model);

        $model->name = 'Lorem';
        $model->number = 10.75;
        $model->active = true;
        $model->deleted = false;
        $model->ignored_column = 1337;
        $this->assertTrue($model->save());

        $this->assertNotEmpty($model->rowid);

        // load
        $modelLoaded = ModelSQLite::find($model->rowid);
        $this->assertNotEmpty($modelLoaded);
        $this->assertInstanceOf(ModelSQLite::class, $modelLoaded);

        $this->assertEquals($model->name, $modelLoaded->name);
        $this->assertEquals($model->number, $modelLoaded->number);
        $this->assertEmpty($modelLoaded->ignored_column);

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
        $this->assertInstanceOf(QueryBuilder::class, $query);

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
        $this->assertInstanceOf(ModelSQLite::class, $queryModel);
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
        $result = ModelSQLiteCompound::findAll();
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
        $this->assertInstanceOf(ModelSQLiteCompound::class, $model);
        $this->assertEquals(5, $model->foo_id);
        $this->assertEquals(10, $model->bar_id);

        // query many
        $query = ModelSQLiteCompound::query();
        $query->whereGt('foo_id', 6);
        $query->orderByAsc('foo_id');
        $result = $query->findMany();

        $this->assertInstanceOf(QueryIterator::class, $result);

        foreach ($result as $model) {
            /** @var ModelSQLiteCompound $model */

            // check if correct model
            $this->assertInstanceOf(ModelSQLiteCompound::class, $model);

            // check if not filtered item
            $this->assertNotEquals($model1->foo_id, $model->foo_id);
        }

        // query buffered
        $result = ModelSQLiteCompound::findAll();
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(ModelSQLiteCompound::class, $result);
        $this->assertCount(3, $result);
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
            $this->assertInstanceOf(ModelSQLiteCompound::class, $model);
        }

        // here the exception should get thrown
        foreach ($result as $model) {
            $this->assertInstanceOf(ModelSQLiteCompound::class, $model);
        }
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionCode 23000
     */
    public function testUniqueKeyHandling()
    {
        $userA = ModelSQLite::create();
        $userA->name = 'User A';
        $userA->email = 'info@example.org';
        $this->assertTrue($userA->save());

        $userB = ModelSQLite::create();
        $userB->name = 'User B';
        $userB->email = 'info@example.org';
        $userB->save();
    }
}
