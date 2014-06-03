<?php
/**
 * @author: remy
 */
namespace RormTest;

use PHPUnit_Framework_TestCase;
use Rorm\Model;
use Rorm\Rorm;

/**
 * Class RormTest
 */
class RormTest extends PHPUnit_Framework_TestCase
{

    public function testDefaultConnection()
    {
        $this->assertEquals(
            Model::$_connection,
            Rorm::CONNECTION_DEFAULT
        );
    }

    public function testGetDatabase()
    {
        $this->assertInstanceOf('PDO', Rorm::getDatabase());
        $this->assertNull(Rorm::getDatabase('unknown_connection'));
    }

    /**
     * @depends testGetDatabase
     */
    public function testSetDatabase()
    {
        $db = Rorm::getDatabase();
        Rorm::setDatabase($db);
        $this->assertEquals($db, Rorm::getDatabase());
    }

    /**
     * @return array
     */
    public function providerQuoteIdentifier()
    {
        return array(
            array('test', '`test`'),
            array('lorem ipsum', '`lorem ipsum`'),
            array('te`st', '`te``st`'),
            array('`test`', '```test```'),
        );
    }

    /**
     * @param $value
     * @param $expected
     *
     * @dataProvider providerQuoteIdentifier
     */
    public function testQuoteIdentifier($value, $expected)
    {
        $this->assertEquals($expected, Rorm::quoteIdentifier($value));
    }
}
