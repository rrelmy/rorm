<?php
/**
 * @author: remy
 */

namespace RormTest;

use PHPUnit\Framework\TestCase;
use Rorm\Query;
use RormTest\Model\TestBasic;

/**
 * Class QueryTest
 * @package RormTest
 */
class QueryTest extends TestCase
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

        $params = [1, 2, 3];
        $query->setParams($params);

        $this->assertEquals($params, $query->getParams());
    }

    /**
     * @depends testConstruct
     */
    public function testCreateInstanceModel()
    {
        $query = new Query(TestBasic::class);
        /** @var TestBasic $instance */
        $instance = $query->instanceFromObject(
            [
                'id' => 7,
                'name' => 'Test',
                'number' => 75.3,
                'active' => true,
                'deleted' => false,
            ]
        );

        $this->assertInstanceOf(TestBasic::class, $instance);
        $this->assertEquals(7, $instance->id);
        $this->assertEquals('Test', $instance->name);
        $this->assertEquals(75.3, $instance->number);
        $this->assertTrue($instance->active);
        $this->assertFalse($instance->deleted);
    }

    /**
     * @depends testConstruct
     */
    public function testCreateInstanceCustom()
    {
        $query = new Query();
        /** @var \stdClass $instance */
        $instance = $query->instanceFromObject(
            [
                'id' => 7,
                'name' => 'Test',
                'number' => 75.3,
                'active' => true,
                'deleted' => false,
            ]
        );

        $this->assertInstanceOf('stdClass', $instance);
        $this->assertEquals(7, $instance->id);
        $this->assertEquals('Test', $instance->name);
        $this->assertEquals(75.3, $instance->number);
        $this->assertTrue($instance->active);
        $this->assertFalse($instance->deleted);
    }
}
