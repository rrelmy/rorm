<?php
/**
 * @author: remy
 */

namespace RormTest;

use PHPUnit\Framework\TestCase;
use RormTest\Model\Compound;
use RormTest\Model\DifferentIdField;
use RormTest\Model\TestBasic;
use stdClass;

/**
 * Class ModelBasicTest
 * @package RormTest
 *
 * testing the data access and write methods
 * the extended class tests for the database stuff
 */
class ModelBasicTest extends TestCase
{

    public function testSetterGetter()
    {
        $object = TestBasic::create();

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
        $object = TestBasic::create();
        $object->setData(
            [
                'id' => 10,
                'active' => true,
            ]
        );
        $this->assertEquals(10, $object->get('id'));
        $this->assertEquals(true, $object->active);
    }

    /**
     * @depends testSetterGetter
     */
    public function testHas()
    {
        $object = TestBasic::create();
        $object->name = 'foo';
        $this->assertTrue(isset($object->name));
        $this->assertFalse(isset($object->doesNotExist));
    }

    /**
     * @depends testSetterGetter
     */
    public function testRemove()
    {
        $object = TestBasic::create();

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
        /** @var TestBasic $object */
        $object = TestBasic::create();
        $object->setData(
            [
                'id' => 1,
                'name' => 'ipsum',
                'active' => true,
                'deleted' => false,
            ]
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
        $a = TestBasic::create();
        $a->setData(
            [
                'id' => 1,
                'name' => 'ipsum',
                'active' => true,
            ]
        );

        $b = TestBasic::create();
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

        $b = TestBasic::create();
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
        $a = TestBasic::create();
        $data = [
            'id' => 1,
            'name' => 'ipsum',
            'active' => true,
        ];
        $a->setData($data);

        $this->assertEquals(json_encode($data), json_encode($a));
    }

    /**
     * @depends testSetterGetter
     * @depends testSetMultiple
     */
    public function testGetAndHasId()
    {
        // normal id
        $a = TestBasic::create();
        $this->assertFalse($a->hasId());
        $a->set('id', 768);
        $this->assertTrue($a->hasId());
        $this->assertEquals(768, $a->getId());

        // custom id
        $a = DifferentIdField::create();
        $this->assertFalse($a->hasId());
        $a->set('custom_id', 98765);
        $this->assertTrue($a->hasId());
        $this->assertEquals(98765, $a->getId());

        // compound keys
        $compoundModel = Compound::create();
        $this->assertFalse($compoundModel->hasId());
        $compoundModel->setData(
            [
                'foo_id' => 1,
                'bar_id' => 77,
                'name' => 'Foo Bar',
            ]
        );

        $this->assertTrue($compoundModel->hasId());
        $this->assertEquals(['foo_id' => 1, 'bar_id' => 77], $compoundModel->getId());
    }
}
