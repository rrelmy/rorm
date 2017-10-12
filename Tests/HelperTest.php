<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /** @var Helper */
    private $helper;

    protected function setUp()
    {
        $this->helper = new Helper();
    }

    /**
     * @return \PDO|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMySQLConnection(): \PDO
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
    private function createSQLiteConnection(): \PDO
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
     * @covers \Rorm\Helper::isMySQL
     * @covers   \Rorm\Helper::isSQLite
     */
    public function testIsMySQL()
    {
        $connection = $this->createMySQLConnection();

        $this->assertTrue($this->helper->isMySQL($connection));
        $this->assertFalse($this->helper->isSQLite($connection));
    }

    /**
     * @covers \Rorm\Helper::isSQLite
     * @covers   \Rorm\Helper::isMySQL
     */
    public function testIsSQLite()
    {
        $connection = $this->createSQLiteConnection();

        $this->assertTrue($this->helper->isSQLite($connection));
        $this->assertFalse($this->helper->isMySQL($connection));
    }

    /**
     * @covers \Rorm\Helper::quote
     */
    public function testQuote()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);

        $this->assertEquals('NULL', $this->helper->quote($connection, null));
        $this->assertEquals(0, $this->helper->quote($connection, 0));
        $this->assertEquals(10, $this->helper->quote($connection, 10));
        $this->assertEquals(0.5, $this->helper->quote($connection, 0.5));
        $this->assertEquals(7.777, $this->helper->quote($connection, 7.777));

        $quoteString = 'foobar';
        $connection
            ->expects($this->once())
            ->method('quote')
            ->with($quoteString);

        $this->helper->quote($connection, $quoteString);
    }

    /**
     * @covers \Rorm\Helper::quote
     * @uses   \Rorm\Helper::isMySQL
     */
    public function testQuoteMySQL()
    {
        $connection = $this->createMySQLConnection();

        $this->assertEquals('TRUE', $this->helper->quote($connection, true));
        $this->assertEquals('FALSE', $this->helper->quote($connection, false));
    }

    /**
     * @covers \Rorm\Helper::quote
     * @uses   \Rorm\Helper::isMySQL
     */
    public function testQuoteSQLite()
    {
        $connection = $this->createSQLiteConnection();

        $this->assertEquals(1, $this->helper->quote($connection, true));
        $this->assertEquals(0, $this->helper->quote($connection, false));
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
     * @covers       \Rorm\Helper::getIdentifierQuoter
     * @uses         \Rorm\Helper::isMySQL
     */
    public function testIdentifierQuoterMySQL($value, string $expected)
    {
        $connection = $this->createMySQLConnection();

        $quoter = $this->helper->getIdentifierQuoter($connection);
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
     * @covers       \Rorm\Helper::getIdentifierQuoter
     * @uses         \Rorm\Helper::isMySQL
     */
    public function testIdentifierQuoter($value, string $expected)
    {
        $connection = $this->createSQLiteConnection();

        $quoter = $this->helper->getIdentifierQuoter($connection);
        $this->assertEquals($expected, $quoter($value));
    }
}
