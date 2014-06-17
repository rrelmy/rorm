<?php

namespace RormTest;

use Rorm\Rorm;
use RormTest\Test\Compound;
use PHPUnit_Framework_TestCase;
use Exception;
use PDOException;
use Test_Basic;

/**
 * @author: remy
 */
class QueryDatabaseBasicTest extends PHPUnit_Framework_TestCase
{
    public function testDbhFlag()
    {
        $this->assertTrue(Rorm::getDatabase()->isMySQL);
    }

    /**
     * @depends testDbhFlag
     */
    public function testQuoteIdentifier()
    {
        $quoter = Rorm::getIdentifierQuoter();
        $this->assertEquals('`mysql`', $quoter('mysql'));
    }

    /**
     * @expectedException \Rorm\QueryException
     */
    public function testSaveEmpty()
    {
        Test_Basic::create()->save();
    }

    /**
     * @expectedException PDOException
     */
    public function testCustomQueryError()
    {
        $query = Test_Basic::customQuery('SELECT PLAIN WRONG QUERY;');
        $query->findOne();
    }

    /**
     * @depends testSaveEmpty
     */
    public function testBasic()
    {
        $model = Test_Basic::create();
        $this->assertInstanceOf('Test_Basic', $model);

        $model->name = 'Lorem';
        $model->number = 10.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        $this->assertNotEmpty($model->id);

        // load
        $modelLoaded = Test_Basic::find($model->id);
        $this->assertNotEmpty($modelLoaded);
        $this->assertInstanceOf('Test_Basic', $modelLoaded);

        $this->assertEquals($model->name, $modelLoaded->name);
        $this->assertEquals($model->number, $modelLoaded->number);
        $this->assertNotEmpty($modelLoaded->modified);

        // update
        $model->name = 'Lorem ipsum';
        $this->assertTrue($model->save());

        // re load
        $modelLoaded = Test_Basic::find($model->id);
        $this->assertEquals($model->id, $modelLoaded->id);

        // sleep to check the modified column with ignoreColumn
        sleep(1.2);

        // update loaded (test ignore fields)
        $modelLoaded->number += 1;
        $this->assertTrue($modelLoaded->save());
        $this->assertEquals(11.75, $modelLoaded->number);

        $modelLoadedAgain = Test_Basic::find($model->id);
        $this->assertEquals($model->name, $modelLoadedAgain->name);
        $this->assertNotEquals($modelLoaded->modified, $modelLoadedAgain->modified);


        // delete
        $this->assertTrue($model->delete());

        // re load empty
        $this->assertNull(Test_Basic::find($model->id));
    }

    /**
     * @depends testBasic
     */
    public function testBasicQueryBuilder()
    {
        // create some data
        $model = Test_Basic::create();
        $model->name = 'QueryBuilder';
        $model->number = 17.75;
        $model->active = true;
        $model->deleted = false;
        $this->assertTrue($model->save());

        // query data
        $query = Test_Basic::query();
        $this->assertInstanceOf('\\Rorm\\QueryBuilder', $query);

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
        $this->assertInstanceOf('Test_Basic', $queryModel);
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
        $this->assertNull(Test_Basic::find('not existing id'));
    }

    /**
     * @depends testBasic
     * @depends testNullIfNotFound
     */
    public function testCompound()
    {
        // check if empty
        $result = Test\Compound::query()->findAll();
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);

        // create
        $model1 = Test\Compound::create();
        $model1->foo_id = 5;
        $model1->bar_id = 10;
        $model1->name = '5 to 10';
        $this->assertTrue($model1->save());

        // create
        $model2 = Test\Compound::create();
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
        $model4 = Test\Compound::create();
        $model4->foo_id = 11;
        $model4->bar_id = 8;
        $model4->name = '11 to 8';
        $this->assertTrue($model4->save());
        $this->assertTrue($model4->delete());

        // query one
        $model = Test\Compound::find(5, 10);
        $this->assertInstanceOf('\\RormTest\\Test\\Compound', $model);
        $this->assertEquals(5, $model->foo_id);
        $this->assertEquals(10, $model->bar_id);

        // query many
        $query = Test\Compound::query();
        $query->whereGt('foo_id', 6);
        $query->orderByAsc('foo_id');
        $result = $query->findMany();

        $this->assertInstanceOf('\\Rorm\\QueryIterator', $result);

        foreach ($result as $model) {
            /** @var Compound $model */

            // check if correct model
            $this->assertInstanceOf('\\RormTest\\Test\\Compound', $model);

            // check if not filtered item
            $this->assertNotEquals($model1->foo_id, $model->foo_id);
        }

        // query buffered
        $result = Test\Compound::query()->findAll();
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf('\\RormTest\\Test\\Compound', $result);
        $this->assertEquals(3, count($result));
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
            $this->assertInstanceOf('\\RormTest\\Test\\Compound', $model);
        }

        // here the exception should get thrown
        foreach ($result as $model) {
            $this->assertInstanceOf('\\RormTest\\Test\\Compound', $model);
        }
    }
}
