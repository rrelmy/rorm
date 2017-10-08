<?php
/**
 * @author Rémy M. Böhler
 */
declare(strict_types=1);

namespace Rorm;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Rorm\Rorm::__construct()
 * @covers \Rorm\Rorm::setConnection()
 */
class RormTest extends TestCase
{
    /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject */
    private $defaultConnection;

    /** @var Rorm */
    private $rorm;

    protected function setUp()
    {
        $this->defaultConnection = $this->createMock(\PDO::class);
        $this->rorm = new Rorm($this->defaultConnection);
    }

    /**
     * @covers \Rorm\Rorm::defaultConnection()
     * @covers \Rorm\Rorm::connection()
     */
    public function testDefaultConnection()
    {
        $this->assertEquals($this->defaultConnection, $this->rorm->defaultConnection());
    }

    /**
     * @covers \Rorm\Rorm::connection()
     * @covers \Rorm\Rorm::setConnection()
     */
    public function testGetSetConnection()
    {
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(\PDO::class);
        $name = 'custom';

        $this->rorm->setConnection($name, $connection);
        $this->assertEquals($connection, $this->rorm->connection($name));
    }

    /**
     * @covers \Rorm\Rorm::connection()
     *
     * @expectedException \Rorm\ConnectionNotFoundException
     */
    public function testUnknownConnection()
    {
        $this->rorm->connection('undefined');
    }
}
