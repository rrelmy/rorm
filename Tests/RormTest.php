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
    }

    /**
     * @expectedException \Rorm\Exception
     */
    public function testGetUnknownDatabase()
    {
        Rorm::getDatabase('unknown_connection');
    }

    /**
     * @depends testGetDatabase
     */
    public function testSetDatabase()
    {
        $dbh = Rorm::getDatabase();
        Rorm::setDatabase($dbh);
        $this->assertEquals($dbh, Rorm::getDatabase());
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
