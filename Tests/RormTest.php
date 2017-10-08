<?php
/**
 * @author Rémy M. Böhler
 */
declare(strict_types=1);

namespace RormTest;

use PDO;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\TestCase;
use Rorm\Rorm;

/**
 * TODO proper DBUnit tests, currently only for testing travis
 */
class RormTest extends TestCase
{
    protected function setUp()
    {
        Rorm::reset();
    }

    /**
     * @return \PDO|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMySQLConnection()
    {
        $connection = $this->createMock(\PDO::class);
        $connection
            ->expects($this->any())
            ->method('getAttribute')
            ->with(\PDO::ATTR_DRIVER_NAME)
            ->willReturn('mysql');

        return $connection;
    }

    /**
     * @return \PDO|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSQLiteConnection()
    {
        $connection = $this->createMock(\PDO::class);
        $connection
            ->expects($this->any())
            ->method('getAttribute')
            ->with(\PDO::ATTR_DRIVER_NAME)
            ->willReturn('sqlite');

        return $connection;
    }

    /**
     * @covers \Rorm\Rorm::setDatabase
     * @covers \Rorm\Rorm::getDatabase
     * @uses   \Rorm\Rorm::reset
     */
    public function testGetSetConnection()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);
        $name = 'custom';

        Rorm::setDatabase($connection, $name);
        $this->assertEquals($connection, Rorm::getDatabase($name));
    }

    /**
     * @covers \Rorm\Rorm::getDatabase
     * @uses   \Rorm\Rorm::reset
     *
     * @expectedException \Exception
     */
    public function testUnknownConnection()
    {
        Rorm::getDatabase('custom');
    }

    /**
     * @covers \Rorm\Rorm::setDatabase
     * @covers \Rorm\Rorm::getDatabase
     * @uses   \Rorm\Rorm::reset
     */
    public function testDefaultConnection()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);

        Rorm::setDatabase($connection);

        $this->assertEquals($connection, Rorm::getDatabase());
        $this->assertEquals($connection, Rorm::getDatabase(Rorm::CONNECTION_DEFAULT));
    }

    /**
     * @expectedException \Rorm\ConnectionNotFoundException
     * @depends testUnknownConnection
     *
     * @covers  \Rorm\Rorm::reset
     * @uses    \Rorm\Rorm::setDatabase
     * @uses    \Rorm\Rorm::getDatabase
     */
    public function testReset()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);

        Rorm::setDatabase($connection);
        Rorm::reset();
        Rorm::getDatabase();
    }

    /**
     * @covers \Rorm\Rorm::isMySQL
     * @uses   \Rorm\Rorm::isSQLite
     * @uses   \Rorm\Rorm::reset
     */
    public function testIsMySQL()
    {
        $connection = $this->createMySQLConnection();

        $this->assertTrue(Rorm::isMySQL($connection));
        $this->assertFalse(Rorm::isSQLite($connection));
    }

    /**
     * @covers \Rorm\Rorm::isSQLite
     * @uses   \Rorm\Rorm::isMySQL
     * @uses   \Rorm\Rorm::reset
     */
    public function testIsSQLite()
    {
        $connection = $this->createSQLiteConnection();

        $this->assertTrue(Rorm::isSQLite($connection));
        $this->assertFalse(Rorm::isMySQL($connection));
    }

    /**
     * @covers \Rorm\Rorm::quote
     * @uses   \Rorm\Rorm::reset
     */
    public function testQuote()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);

        $this->assertEquals('NULL', Rorm::quote($connection, null));
        $this->assertEquals(0, Rorm::quote($connection, 0));
        $this->assertEquals(10, Rorm::quote($connection, 10));
        $this->assertEquals(0.5, Rorm::quote($connection, 0.5));
        $this->assertEquals(7.777, Rorm::quote($connection, 7.777));

        $quoteString = 'foobar';
        $connection
            ->expects($this->once())
            ->method('quote')
            ->with($quoteString);

        Rorm::quote($connection, $quoteString);
    }

    /**
     * @covers \Rorm\Rorm::quote
     * @uses   \Rorm\Rorm::isMySQL
     * @uses   \Rorm\Rorm::reset
     */
    public function testQuoteMySQL()
    {
        $connection = $this->createMySQLConnection();

        $this->assertEquals('TRUE', Rorm::quote($connection, true));
        $this->assertEquals('FALSE', Rorm::quote($connection, false));
    }

    /**
     * @covers \Rorm\Rorm::quote
     * @uses   \Rorm\Rorm::isMySQL
     * @uses   \Rorm\Rorm::reset
     */
    public function testQuoteSQLite()
    {
        $connection = $this->createSQLiteConnection();

        $this->assertEquals(1, Rorm::quote($connection, true));
        $this->assertEquals(0, Rorm::quote($connection, false));
    }

    public function mysqlIdentifierProvider(): array
    {
        return [
            ['test', '`test`'],
            ['it`s great', '`it``s great`'],
            ['lorem```ipsum', '`lorem``````ipsum`'],
            [1234, '`1234`'],
        ];
    }

    /**
     * @dataProvider mysqlIdentifierProvider
     *
     * @covers       \Rorm\Rorm::getIdentifierQuoter
     * @uses         \Rorm\Rorm::isMySQL
     * @uses         \Rorm\Rorm::reset
     */
    public function testIdentifierQuoterMySQL($value, string $expected)
    {
        $connection = $this->createMySQLConnection();

        $quoter = Rorm::getIdentifierQuoter($connection);
        $this->assertEquals($expected, $quoter($value));
    }

    public function identifierProvider(): array
    {
        return [
            ['test', '"test"'],
            ['it"s great', '"it""s great"'],
            ['lorem""ipsum', '"lorem""""ipsum"'],
            [1234, '"1234"'],
        ];
    }

    /**
     * @dataProvider identifierProvider
     *
     * @covers       \Rorm\Rorm::getIdentifierQuoter
     * @uses         \Rorm\Rorm::isMySQL
     * @uses         \Rorm\Rorm::reset
     */
    public function testIdentifierQuoter($value, string $expected)
    {
        $connection = $this->createSQLiteConnection();

        $quoter = Rorm::getIdentifierQuoter($connection);
        $this->assertEquals($expected, $quoter($value));
    }

    protected function getConnection(): Connection
    {
        $pdo = new PDO('sqlite::memory:');
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    protected function getDataSet(): IDataSet
    {
        return new DefaultDataSet();
    }
}
