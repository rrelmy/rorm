<?php
namespace RormTest;

use PHPUnit_Framework_TestCase;
use Rorm\Query;

/**
 * @author: remy
 */
class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $query = new Query();
        $this->assertEquals('stdClass', $query->getClass());

        $className = 'Test_Basic';
        $query = new Query($className);
        $this->assertEquals($className, $query->getClass());
    }

    /**
     * @depends testConstruct
     */
    public function testQuery()
    {
        $query = new Query();
        $this->assertNull($query->getQuery());

        $sql = 'SELECT 1 FROM `test`';
        $query->setQuery($sql);

        $this->assertEquals($sql, $query->getQuery());
    }

    /**
     * @depends testQuery
     */
    public function testParams()
    {
        $query = new Query();
        $this->assertNull($query->getParams());

        $params = array(1, 2, 3);
        $query->setParams($params);

        $this->assertEquals($params, $query->getParams());
    }
}
