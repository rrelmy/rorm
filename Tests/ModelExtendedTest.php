<?php

namespace RormTest;

use PHPUnit_Framework_TestCase;
use RormTest\Test\FixedName;
use Test_Basic;

/**
 * @author: remy
 */
class ModelExtendedTest extends PHPUnit_Framework_TestCase
{
    public function testTableName()
    {
        $this->assertEquals('test_basic', Test_Basic::getTable());
        $this->assertEquals('test_table', FixedName::getTable());
    }

    public function testQuery()
    {
        $query = Test_Basic::query();
        $this->assertInstanceOf('\\Rorm\\QueryBuilder', $query);
        $this->assertEquals('Test_Basic', $query->getClass());
        $this->assertEquals(Test_Basic::getTable(), $query->getTable());
    }

    public function testCustomQuery()
    {
        // basic
        $sqlBasic = 'SELECT id, name FROM test_basic ORDER BY modified';
        $queryBasic = Test_Basic::customQuery($sqlBasic);
        $this->assertInstanceOf('\\Rorm\\Query', $queryBasic);
        $this->assertNotInstanceOf('\\Rorm\\QueryBuilder', $queryBasic);
        $this->assertEquals('Test_Basic', $queryBasic->getClass());
        $this->assertEquals($sqlBasic, $queryBasic->getQuery());
        $this->assertEmpty($queryBasic->getParams());

        // params
        $sqlParams = 'SELECT id, name FROM test_basic WHERE active = ? ORDER BY modified';
        $params = array(75);
        $queryParam = Test_Basic::customQuery($sqlParams, $params);
        $this->assertEquals($sqlParams, $queryParam->getQuery());
        $this->assertEquals($params, $queryParam->getParams());
    }
}
