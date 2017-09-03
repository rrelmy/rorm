<?php
/**
 * @author: remy
 */

namespace RormTest;

use Exception;
use PDOException;
use PHPUnit\Framework\TestCase;
use Rorm\QueryBuilder;
use Rorm\QueryIterator;
use Rorm\Rorm;
use RormTest\Model\Compound;
use RormTest\Model\TestBasic;

/**
 * Class QueryDatabaseBasicTest
 * @package RormTest
 */
class QueryDatabaseBasicTest extends TestCase
{
    public function testDbDriver()
    {
        $dbh = Rorm::getDatabase();
        $this->assertTrue(Rorm::isMySQL($dbh));
        $this->assertFalse(Rorm::isSQLite($dbh));
    }

    /**
     * @depends testDbDriver
     */
    public function testQuote()
    {
        $dbh = Rorm::getDatabase();

        $this->assertEquals('TRUE', Rorm::quote($dbh, true));
        $this->assertEquals('FALSE', Rorm::quote($dbh, false));
        $this->assertEquals('NULL', Rorm::quote($dbh, null));
        $this->assertEquals(17, Rorm::quote($dbh, 17));
        $this->assertEquals(28.75, Rorm::quote($dbh, 28.75));
        $this->assertInternalType('integer', Rorm::quote($dbh, 10));
        $this->assertInternalType('float', Rorm::quote($dbh, 10.6));
        $this->assertEquals("'lorem'", Rorm::quote($dbh, 'lorem'));
        // todo test object with __toString
    }

    /**
     * @expectedException \Rorm\QueryException
     */
    public function testSaveEmpty()
    {
        TestBasic::create()->save();
    }

    /**
     * @expectedException PDOException
     */
    public function testCustomQueryError()
    {
        $query = TestBasic::customQuery('SELECT PLAIN WRONG QUERY;');
        $query->findOne();
    }

    /**
     * @depends testSaveEmpty
     */
    public function testBasic()
    {
        // find empty
        $this->assertEmpty(TestBasic::query()->findColumn());
        $this->assertEmpty(TestBasic::query()->findOne());

        // create entry
        $model = TestBasic::create();
        $this->assertInstanceOf(TestBasic::class, $model);

        $model->name = 'Lorem';
        $model->number = 10.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        $this->assertNotEmpty($model->id);

        // load
        $modelLoaded = TestBasic::find($model->id);
        $this->assertNotEmpty($modelLoaded);
        $this->assertInstanceOf(TestBasic::class, $modelLoaded);

        $this->assertEquals($model->name, $modelLoaded->name);
        $this->assertEquals($model->number, $modelLoaded->number);
        $this->assertNotEmpty($modelLoaded->modified);

        // update
        $model->name = 'Lorem ipsum';
        $model->remove('active');
        $this->assertTrue($model->save());

        // re load
        $modelLoaded = TestBasic::find($model->id);
        $this->assertEquals($model->id, $modelLoaded->id);
        $this->assertNull($modelLoaded->active, 'remove() should remove the data');

        // sleep to check the modified column with ignoreColumn
        sleep(1.2);

        // update loaded (test ignore fields)
        ++$modelLoaded->number;
        $this->assertTrue($modelLoaded->save());
        $this->assertEquals(11.75, $modelLoaded->number);

        $modelLoadedAgain = TestBasic::find($model->id);
        $this->assertEquals($model->name, $modelLoadedAgain->name);
        $this->assertNotEquals($modelLoaded->modified, $modelLoadedAgain->modified);


        // delete
        $this->assertTrue($model->delete());

        // re load empty
        $this->assertNull(TestBasic::find($model->id));
    }

    /**
     * @depends testBasic
     */
    public function testColumn()
    {
        // create some data
        $model = TestBasic::create();
        $model->name = 'QueryBuilder';
        $model->number = 17.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        $result = TestBasic::query()->select('name')->whereId($model->id)->findColumn();
        $this->assertEquals('QueryBuilder', $result);
    }

    /**
     * @depends testBasic
     */
    public function testBasicQueryBuilder()
    {
        // create some data
        $model = TestBasic::create();
        $model->name = 'QueryBuilder';
        $model->number = 17.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        // query data
        $query = TestBasic::query();
        $this->assertInstanceOf(QueryBuilder::class, $query);

        $query
            ->selectAll()
            ->select('deleted', 'deleted2')
            ->selectExpr('number + 10', 'higher_number')
            ->where('active', true)
            ->where('deleted', false)
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
        $this->assertInstanceOf(TestBasic::class, $queryModel);
        $this->assertEquals($model->getId(), $queryModel->getId());

        // test boolean parameters
        $this->assertTrue((bool)$queryModel->active);
        $this->assertFalse((bool)$queryModel->deleted);
    }

    /**
     * @depends testBasic
     */
    public function testNullIfNotFound()
    {
        $this->assertNull(TestBasic::find('not existing id'));
    }

    /**
     * @depends testBasic
     * @depends testNullIfNotFound
     */
    public function testCompound()
    {
        // check if empty
        $result = Compound::findAll();
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);

        // create
        $model1 = Compound::create();
        $model1->foo_id = 5;
        $model1->bar_id = 10;
        $model1->name = '5 to 10';
        $this->assertTrue($model1->save());

        // create
        $model2 = Compound::create();
        $model2->foo_id = 7;
        $model2->bar_id = 10;
        $model2->name = '7 to 10';
        $this->assertTrue($model2->save());

        // create
        $model3 = Compound::create();
        $model3->foo_id = 11;
        $model3->bar_id = 1;
        $model3->name = '11 to 1';
        $this->assertTrue($model3->save());


        // create and delete
        $model4 = Compound::create();
        $model4->foo_id = 11;
        $model4->bar_id = 8;
        $model4->name = '11 to 8';
        $this->assertTrue($model4->save());
        $this->assertTrue($model4->delete());

        // query one
        $model = Compound::find(5, 10);
        $this->assertInstanceOf(Compound::class, $model);
        $this->assertEquals(5, $model->foo_id);
        $this->assertEquals(10, $model->bar_id);

        // query many
        $query = Compound::query();
        $query->whereGt('foo_id', 6);
        $query->orderByAsc('foo_id');
        $result = $query->findMany();

        $this->assertInstanceOf(QueryIterator::class, $result);

        foreach ($result as $model) {
            /** @var Compound $model */

            // check if correct model
            $this->assertInstanceOf(Compound::class, $model);

            // check if not filtered item
            $this->assertNotEquals($model1->foo_id, $model->foo_id);
        }

        // query buffered
        $result = Compound::findAll();
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(Compound::class, $result);
        $this->assertCount(3, $result);
    }

    // TODO check querybuilder with compound keys

    /**
     * @depends testCompound
     * @expectedException Exception
     */
    public function testQueryRewind()
    {
        $result = Compound::query()->findMany();
        $this->assertNotEmpty($result);

        foreach ($result as $model) {
            $this->assertInstanceOf(Compound::class, $model);
        }

        // here the exception should get thrown
        foreach ($result as $model) {
            $this->assertInstanceOf(Compound::class, $model);
        }
    }

    public function testCount()
    {
        $dbh = TestBasic::getDatabase();

        // clear table
        $dbh->exec('TRUNCATE TABLE test_basic;');

        // insert data
        $insert = $dbh->prepare('INSERT INTO test_basic (name, number, active, deleted) VALUES(?, ?, ?, ?)');

        $insert->execute(array('Lorem', 10, 1, 0));
        $insert->execute(array('ipsum', 17, 1, 0));
        $insert->execute(array('dolor', 12.5, 1, 0));
        $insert->execute(array('sit amet', -10, 1, 0));
        $insert->execute(array('Deleted', 0, 0, 1));
        $insert->execute(array('Inactive', 0, 0, 0));

        // count with query builder
        $query = TestBasic::query()->where('active', true)->where('deleted', false)->orderByAsc('number');
        $this->assertInternalType('integer', $query->count());
        $this->assertEquals(4, $query->count());

        // check result after count
        $model = $query->findOne();
        $this->assertInstanceOf(TestBasic::class, $model);
        $this->assertEquals(-10, $model->number);

        // count with customQuery (inefficient!)
        $customQuery = TestBasic::customQuery(
            'SELECT *
            FROM test_basic
            WHERE active = TRUE AND deleted = FALSE
            ORDER BY number ASC'
        );
        $this->assertEquals(4, $customQuery->count());

        // check result after count
        $model = $customQuery->findOne();
        $this->assertInstanceOf(TestBasic::class, $model);
        $this->assertEquals(-10, $model->number);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionCode 23000
     */
    public function testUniqueKeyHandling()
    {
        $userA = TestBasic::create();
        $userA->name = 'User A';
        $userA->email = 'info@example.org';
        $this->assertTrue($userA->save());

        $userB = TestBasic::create();
        $userB->name = 'User B';
        $userB->email = 'info@example.org';
        $userB->save();
    }
}
