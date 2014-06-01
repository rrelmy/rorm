<?php

namespace RormTest;

use PHPUnit_Framework_TestCase;
use RormTest\Test\DifferentIdField;
use stdClass;
use Test_Basic;

/**
 * testing the data access and write methods
 * the extended class tests for the database stuff
 *
 * @author: remy
 */
class ModelBasicTest extends PHPUnit_Framework_TestCase
{

    public function testSetterGetter()
    {
        $object = Test_Basic::create();

        $this->assertEmpty($object->getData());

        $this->assertEquals($object, $object->set('name', 'foo'));
        $this->assertEquals('foo', $object->get('name'));
        $this->assertEquals('foo', $object->name);
        $this->assertNull($object->get('non'));
    }

    /**
     * @depends testSetterGetter
     */
    public function testSetMultiple()
    {
        $object = Test_Basic::create();
        $object->setData(
            array(
                'id' => 10,
                'active' => true,
            )
        );
        $this->assertEquals(10, $object->get('id'));
        $this->assertEquals(true, $object->active);
    }

    /**
     * @depends testSetterGetter
     */
    public function testHas()
    {
        $object = Test_Basic::create();
        $object->name = 'foo';
        $this->assertTrue(isset($object->name));
        $this->assertFalse(isset($object->doesNotExist));
    }

    /**
     * @depends testSetterGetter
     */
    public function testRemove()
    {
        $object = Test_Basic::create();

        $object->set('name', 'foo');
        $this->assertTrue(isset($object->name));
        unset($object->name);
        $this->assertFalse(isset($object->name));
    }

    /**
     * @depends testSetMultiple
     */
    public function testIterator()
    {
        $object = Test_Basic::create();
        $object->setData(
            array(
                'id' => 1,
                'name' => 'ipsum',
                'active' => true,
                'deleted' => false,
            )
        );

        foreach ($object as $key => $value) {
            switch ($key) {
                case 'id':
                    $this->assertEquals(1, $value);
                    break;
                case 'name':
                    $this->assertEquals('ipsum', $value);
                    break;
                case 'active':
                    $this->assertTrue($value);
                    break;
                case 'deleted':
                    $this->assertFalse($value);
                    break;
                default:
                    $this->fail('unknown item (' . $key . ')');
            }
        }
    }

    /**
     * @depends testSetMultiple
     */
    public function testCopyModel()
    {
        $a = Test_Basic::create();
        $a->setData(
            array(
                'id' => 1,
                'name' => 'ipsum',
                'active' => true,
            )
        );

        $b = Test_Basic::create();
        $b->copyDataFrom($a);
        $this->assertEquals($a->getData(), $b->getData());
    }

    /**
     * @depends testCopyModel
     */
    public function testCopyStdClass()
    {
        $a = new stdClass();
        $a->id = 1;
        $a->name = 'ipsum';
        $a->active = true;

        $b = Test_Basic::create();
        $b->copyDataFrom($a);

        $this->assertEquals($b->id, 1);
        $this->assertEquals($b->name, 'ipsum');
        $this->assertTrue($b->active);
    }

    /**
     * @depends testSetMultiple
     */
    public function testJsonEncode()
    {
        $a = Test_Basic::create();
        $data = array(
            'id' => 1,
            'name' => 'ipsum',
            'active' => true,
        );
        $a->setData($data);

        $this->assertEquals(json_encode($data), json_encode($a));
    }

    /**
     * @depends testSetterGetter
     * @depends testSetMultiple
     */
    public function testGetId()
    {
        // normal id
        $a = Test_Basic::create();
        $a->set('id', 768);
        $this->assertEquals(768, $a->getId());

        // custom id
        $a = DifferentIdField::create();
        $a->set('custom_id', 98765);
        $this->assertEquals(98765, $a->getId());

        // compound keys
        $compoundModel = Test\Compound::create();
        $compoundModel->setData(
            array(
                'foo_id' => 1,
                'bar_id' => 77,
                'name' => 'Foo Bar',
            )
        );

        $this->assertEquals(array('foo_id' => 1, 'bar_id' => 77), $compoundModel->getId());
    }
}
