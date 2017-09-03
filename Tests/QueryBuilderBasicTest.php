<?php
/**
 * @author: remy
 */

namespace RormTest;

use PHPUnit\Framework\TestCase;
use Rorm\QueryBuilder;
use RormTest\Model\Compound;
use RormTest\Model\QueryModel;

/**
 * Class QueryBuilderBasicTest
 * @package RormTest
 */
class QueryBuilderBasicTest extends TestCase
{

    public function testSelectMethods()
    {
        $query = QueryModel::query();
        $query
            ->distinct()
            ->selectAll()
            ->select('name')
            ->select('name', 'othername')
            ->selectExpr('YEAR(NOW())')
            ->select('count');

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testWhereMethods()
    {
        $query = QueryModel::query();
        $query
            ->where('id', 1)
            ->whereExpr('year', 'YEAR(NOW())')
            ->whereGt('count', 10)
            ->whereGte('count', 10)
            ->whereLt('count', 20)
            ->whereLte('count', 20)
            ->whereRaw('`test` = YEAR(?)', array('2010-01-01'))
            ->whereNull('field')
            ->whereNotNull('field')
            ->whereNot('field', 123)
            ->whereRaw('`modified` < NOW()')
            ->where('id', 1);

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testOrderMethods()
    {
        $query = QueryModel::query();
        $query
            ->orderByAsc('id')
            ->orderByDesc('modified')
            ->orderByAsc('id');

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testLimitOffset()
    {
        $query = QueryModel::query();
        $query
            ->limit(100)
            ->offset(20)
            ->limit(110);

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testBuild()
    {
        $query = QueryModel::query();
        $this->assertEquals('SELECT * FROM `test`', $query->build()->getQuery());
    }

    /**
     * @depends testBuild
     * @depends testLimitOffset
     */
    public function testBuildLimit()
    {
        $query = QueryModel::query();

        $query->limit(100);
        $this->assertEquals('SELECT * FROM `test` LIMIT 100', $query->build()->getQuery());

        $query->offset(200);
        $this->assertEquals('SELECT * FROM `test` LIMIT 100 OFFSET 200', $query->build()->getQuery());
    }

    /**
     * @depends testSelectMethods
     */
    public function testSelect()
    {
        // query basic
        $query = QueryModel::query();
        $query->select('id');
        $this->assertEquals('SELECT `id` FROM `test`', $query->build()->getQuery());

        // query basic distinct
        $query = QueryModel::query();
        $query
            ->distinct()
            ->select('id');
        $this->assertEquals('SELECT DISTINCT `id` FROM `test`', $query->build()->getQuery());

        // query basic all
        $query = QueryModel::query()
            ->selectAll()
            ->select('id');
        $this->assertEquals('SELECT *, `id` FROM `test`', $query->build()->getQuery());


        // query extended
        $query = QueryModel::query()
            ->select('id')
            ->select('deleted', 'delete');
        $this->assertEquals('SELECT `id`, `deleted` AS `delete` FROM `test`', $query->build()->getQuery());

        // expressions
        $queryExpr = QueryModel::query()
            ->selectExpr('NOW()', 'today')
            ->selectExpr('YEAR(NOW())');
        $this->assertEquals('SELECT NOW() AS `today`, YEAR(NOW()) FROM `test`', $queryExpr->build()->getQuery());
    }

    /**
     * @depends testWhereMethods
     * @depends testBuild
     */
    public function testBuildWhere()
    {
        // basic
        $queryBasic = QueryModel::query();
        $queryBasic->where('id', 1);
        $this->assertEquals('SELECT * FROM `test` WHERE `id` = ?', $queryBasic->build()->getQuery());
        $this->assertEquals(array(1), $queryBasic->getParams());

        // multiple
        $queryMultiple = QueryModel::query()
            ->where('id', 1)
            ->where('name', 'loremipsum')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `id` = ? AND `name` = ?',
            $queryMultiple->getQuery()
        );
        $this->assertEquals(
            array(1, 'loremipsum'),
            $queryMultiple->getParams()
        );

        // id params
        $queryId = QueryModel::query();
        $queryId
            ->whereId(10)
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `id` = ?',
            $queryId->getQuery()
        );
        $this->assertEquals(
            array(10),
            $queryId->getParams()
        );

        // id compound
        $queryIdCompound = Compound::query()
            ->whereId(5, 75)
            ->build();
        $this->assertEquals(
            'SELECT * FROM `rormtest_model_compound` WHERE `foo_id` = ? AND `bar_id` = ?',
            $queryIdCompound->getQuery()
        );
        $this->assertEquals(
            array(5, 75),
            $queryIdCompound->getParams()
        );

        // expression
        $queryExpression = QueryModel::query()
            ->whereExpr('id', '10 + 20')
            ->whereExpr('modified', 'NOW()')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `id` = 10 + 20 AND `modified` = NOW()',
            $queryExpression->getQuery()
        );

        // raw
        $queryRaw = QueryModel::query()
            ->whereRaw('1 < ?', array(20))
            ->whereRaw('`modified` <= NOW()')
            ->whereRaw('SUM(`number`) < ? AND YEAR(NOW()) <= ?', array(100, 2010))
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE 1 < ? AND `modified` <= NOW() AND SUM(`number`) < ? AND YEAR(NOW()) <= ?',
            $queryRaw->getQuery()
        );
        $this->assertEquals(
            array(20, 100, 2010),
            $queryRaw->getParams()
        );

        // lt and gt
        $queryCompare = QueryModel::query();
        $queryCompare
            ->whereLt('id', 10)
            ->whereLte('number', 20)
            ->whereGt('id', 0)
            ->whereGte('number', 75)
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `id` < ? AND `number` <= ? AND `id` > ? AND `number` >= ?',
            $queryCompare->getQuery()
        );
        $this->assertEquals(
            array(10, 20, 0, 75),
            $queryCompare->getParams()
        );

        // not null
        $queryNotNull = QueryModel::query()
            ->whereNotNull('modified')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `modified` IS NOT NULL',
            $queryNotNull->getQuery()
        );

        // null
        $queryNotNull = QueryModel::query()
            ->whereNull('field')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `field` IS NULL',
            $queryNotNull->getQuery()
        );

        // not
        $queryNotNull = QueryModel::query()
            ->whereNot('field', 1234)
            ->build();
        $this->assertEquals(
            "SELECT * FROM `test` WHERE `field` != ?",
            $queryNotNull->getQuery()
        );
    }

    /**
     * @depends testBuildWhere
     */
    public function testBuildWhereIn()
    {
        $query = QueryModel::query()
            ->whereIn('number', array(10, 7, 80.76))
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `number` IN (?, ?, ?)',
            $query->getQuery()
        );
        $this->assertEquals(
            array(10, 7, 80.76),
            $query->getParams()
        );

        // build extended
        $queryExtended = QueryModel::query()
            ->where('number', 10)
            ->whereIn('number', array(10, 7, 80.76))
            ->where('id', 18)
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` WHERE `number` = ? AND `number` IN (?, ?, ?) AND `id` = ?',
            $queryExtended->getQuery()
        );
        $this->assertEquals(
            array(10, 10, 7, 80.76, 18),
            $queryExtended->getParams()
        );
    }

    /**
     * @depends testBuild
     */
    public function testOrder()
    {
        $query = QueryModel::query()
            ->orderByAsc('modified')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` ORDER BY `modified` ASC',
            $query->getQuery()
        );

        $query
            ->orderByDesc('modified')
            ->limit(10)
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` ORDER BY `modified` ASC, `modified` DESC LIMIT 10',
            $query->getQuery()
        );
    }

    /**
     * @depends testBuild
     */
    public function testOrderExpression()
    {
        $query = QueryModel::query()
            ->orderByExpr('RANDOM() ASC')
            ->build();
        $this->assertEquals(
            'SELECT * FROM `test` ORDER BY RANDOM() ASC',
            $query->getQuery()
        );
    }

    /**
     * @depends testBuildWhere
     * @expectedException \Rorm\QueryBuilderException
     */
    public function testWhereIdMismatch()
    {
        $queryId = QueryModel::query();
        $queryId->whereId(1, 5, 10);
    }
}
