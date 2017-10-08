<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Rorm\Query::__construct()
 * @covers \Rorm\Query::getConnection()
 * @covers \Rorm\Query::getClass()
 */
class QueryTest extends TestCase
{
    /** @var ModelBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $modelBuilder;
    /** @var Query */
    private $query;
    /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->createMock(\PDO::class);
        $this->modelBuilder = $this->createMock(ModelBuilder::class);
        $this->query = new Query($this->connection, $this->modelBuilder, \stdClass::class);

        $this->assertEquals($this->connection, $this->query->getConnection());
        $this->assertEquals(\stdClass::class, $this->query->getClass());
    }

    /**
     * @return \PDOStatement|\PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareStatement(): \PDOStatement
    {
        /** @var \PDOStatement|\PHPUnit_Framework_MockObject_MockObject $statement */
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())->method('setFetchMode')->with(\PDO::FETCH_ASSOC);

        $this->connection->expects($this->once())->method('prepare')->willReturn($statement);

        return $statement;
    }

    /**
     * @covers \Rorm\Query::setQuery()
     * @covers \Rorm\Query::getQuery()
     */
    public function testGetSetQuery()
    {
        $query = 'SHOW DATABASES;';

        $this->query->setQuery($query);
        $this->assertEquals($query, $this->query->getQuery());
    }

    /**
     * @covers \Rorm\Query::setParams()
     * @covers \Rorm\Query::getParams()
     */
    public function testGetSetParams()
    {
        $params = [1, 'foo', 'bar'];

        $this->query->setParams($params);
        $this->assertEquals($params, $this->query->getParams());
    }

    /**
     * @covers \Rorm\Query::instanceFromObject()
     */
    public function testInstanceFromObjectGenericClass()
    {
        $model = new \stdClass();
        $this->modelBuilder->expects($this->once())->method('build')->with(\stdClass::class)->willReturn($model);

        $result = $this->query->instanceFromObject(['foo' => 'bar', 'lorem' => true]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertAttributeEquals('bar', 'foo', $result);
        $this->assertAttributeEquals(true, 'lorem', $result);
    }

    /**
     * @covers \Rorm\Query::instanceFromObject()
     */
    public function testInstanceFromObjectModel()
    {
        $data = ['foo' => 'bar', 'lorem' => true];

        $model = $this->createMock(Model::class);
        $model->expects($this->once())->method('setData')->with($data)->willReturn($model);

        $this->modelBuilder->expects($this->once())->method('build')->willReturn($model);

        $this->assertEquals($model, $this->query->instanceFromObject($data));
    }

    /**
     * @covers \Rorm\Query::findColumn()
     * @covers \Rorm\Query::execute()
     */
    public function testFindColumn()
    {
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $statement->expects($this->once())->method('fetchColumn')->willReturn(1337);

        $this->assertEquals(1337, $this->query->findColumn());
    }

    /**
     * @covers \Rorm\Query::findOne()
     * @covers \Rorm\Query::execute()
     * @covers \Rorm\Query::fetch()
     * @uses \Rorm\Query::instanceFromObject()
     */
    public function testFindOne()
    {
        $result = $this->createMock(\stdClass::class);
        $data = ['foo' => 'bar'];
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $statement->expects($this->once())->method('fetch')->willReturn($data);

        $this->modelBuilder->expects($this->once())->method('build')->willReturn($result);

        $this->assertEquals($result, $this->query->findOne());
    }

    /**
     * @covers \Rorm\Query::findOne()
     * @covers \Rorm\Query::fetch()
     * @uses \Rorm\Query::execute()
     */
    public function testFindOneReturnsNullIfNothingIsThere()
    {
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);
        $statement->expects($this->once())->method('fetch')->willReturn(false);

        $this->assertNull($this->query->findOne());
    }

    /**
     * @covers \Rorm\Query::findMany()
     * @covers \Rorm\Query::execute()
     * @uses \Rorm\Query::instanceFromObject()
     */
    public function testFindMany()
    {
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $data = ['id' => 1337];
        $statement->expects($this->exactly(2))->method('fetchObject')->willReturnOnConsecutiveCalls($data, false);

        $obj = $this->createMock(\stdClass::class);
        $this->modelBuilder->expects($this->once())->method('build')->willReturn($obj);

        $result = $this->query->findMany();
        $this->assertInstanceOf(\Generator::class, $result);

        $this->assertEquals($obj, $result->current());
        $result->next();
        $this->assertFalse($result->valid(), 'Generator has only one element');
    }

    /**
     * @covers \Rorm\Query::findAll()
     * @covers \Rorm\Query::execute()
     * @uses \Rorm\Query::findMany()
     * @uses \Rorm\Query::instanceFromObject()
     */
    public function testFindAll()
    {
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $data = ['id' => 1337];
        $statement->expects($this->exactly(2))->method('fetchObject')->willReturnOnConsecutiveCalls($data, false);

        $obj = $this->createMock(\stdClass::class);
        $this->modelBuilder->expects($this->once())->method('build')->willReturn($obj);

        $this->assertEquals([$obj], $this->query->findAll());
    }

    /**
     * @covers \Rorm\Query::count()
     * @covers \Rorm\Query::execute()
     * @uses \Rorm\Query::findAll()
     * @uses \Rorm\Query::findMany()
     * @uses \Rorm\Query::instanceFromObject()
     */
    public function testCount()
    {
        $statement = $this->prepareStatement();
        $statement->expects($this->once())->method('execute')->willReturn(true);

        $data = ['id' => 1337];
        $statement->expects($this->exactly(2))->method('fetchObject')->willReturnOnConsecutiveCalls($data, false);

        $obj = $this->createMock(\stdClass::class);
        $this->modelBuilder->expects($this->once())->method('build')->willReturn($obj);

        $this->assertEquals(1, $this->query->count());
    }

    /**
     * @covers \Rorm\Query::execute()
     * @uses   \Rorm\Query::findOne()
     *
     * @expectedException \PDOException
     * @expectedExceptionCode 1337
     * @expectedExceptionMessage Oops
     */
    public function testExecuteWillThrowExceptionOnFailedQuery()
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())->method('execute')->willReturn(false);
        $statement->expects($this->once())->method('errorInfo')->willReturn('Oops');
        $statement->expects($this->once())->method('errorCode')->willReturn(1337);

        $this->connection->expects($this->once())->method('prepare')->willReturn($statement);

        $this->query->findOne();
    }
}

