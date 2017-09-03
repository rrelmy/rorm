<?php
/**
 * @author: remy
 */

namespace RormTest;

use PHPUnit\Framework\TestCase;
use Rorm\Model;
use Rorm\Rorm;

/**
 * Class RormTest
 * @package RormTest
 */
class RormTest extends TestCase
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
        return [
            ['test', '`test`'],
            ['lorem ipsum', '`lorem ipsum`'],
            ['te`st', '`te``st`'],
            ['`test`', '```test```'],
        ];
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
        return [
            ['test', '"test"'],
            ['lorem ipsum', '"lorem ipsum"'],
            ['te"st', '"te""st"'],
            ['"test"', '"""test"""'],
        ];
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
