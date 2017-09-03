<?php
/**
 * @author: remy
 */
declare(strict_types=1);

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

    public function providerQuoteIdentifierMySQL(): array
    {
        return [
            ['test', '`test`'],
            ['lorem ipsum', '`lorem ipsum`'],
            ['te`st', '`te``st`'],
            ['`test`', '```test```'],
        ];
    }

    /**
     * @dataProvider providerQuoteIdentifierMySQL
     *
     * @fixme this test depends on a mysql database as default
     */
    public function testQuoteIdentifierMySQL(string $value, string $expected)
    {
        $quoter = Rorm::getIdentifierQuoter();
        $this->assertEquals($expected, $quoter($value));
    }

    /**
     * @return array
     */
    public function providerQuoteIdentifier(): array
    {
        return [
            ['test', '"test"'],
            ['lorem ipsum', '"lorem ipsum"'],
            ['te"st', '"te""st"'],
            ['"test"', '"""test"""'],
        ];
    }

    /**
     * @dataProvider providerQuoteIdentifier
     * @fixme this test requires the sqlite connection
     */
    public function testQuoteIdentifier(string $value, string $expected)
    {
        $quoter = Rorm::getIdentifierQuoter(Rorm::getDatabase('sqlite'));
        $this->assertEquals($expected, $quoter($value));
    }
}
