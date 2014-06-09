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
    public function providerQuoteIdentifierMySQL()
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
     * @dataProvider providerQuoteIdentifierMySQL
     *
     * @fixme this test depends on a mysql database as default
     */
    public function testQuoteIdentifierMySQL($value, $expected)
    {
        $quoter = Rorm::getIdentifierQuoter();
        $this->assertEquals($expected, $quoter($value));
    }

    public function testQuote()
    {
        $db = Rorm::getDatabase();

        $this->assertEquals('TRUE', Rorm::quote($db, true));
        $this->assertEquals('FALSE', Rorm::quote($db, false));
        $this->assertEquals('NULL', Rorm::quote($db, null));
        $this->assertInternalType('integer', Rorm::quote($db, 10));
        $this->assertInternalType('float', Rorm::quote($db, 10.6));
        $this->assertEquals("'lorem'", Rorm::quote($db, 'lorem'));
        // todo test object with __toString

    }

    /**
     * @return array
     */
    public function providerQuoteIdentifier()
    {
        return array(
            array('test', '"test"'),
            array('lorem ipsum', '"lorem ipsum"'),
            array('te"st', '"te""st"'),
            array('"test"', '"""test"""'),
        );
    }

    /**
     * @param $value
     * @param $expected
     *
     * @dataProvider providerQuoteIdentifier
     * @fixme this test requires the sqlite connection
     */
    public function testQuoteIdentifier($value, $expected)
    {
        $quoter = Rorm::getIdentifierQuoter(Rorm::getDatabase('sqlite'));
        $this->assertEquals($expected, $quoter($value));
    }
}
