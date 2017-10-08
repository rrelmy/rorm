<?php
/**
 * @author Rémy M. Böhler <code@rrelmy.ch>
 */
declare(strict_types=1);

namespace Rorm;

use PHPUnit\Framework\TestCase;

/**
 * @cover \Rorm\QueryIterator
 */
class QueryIteratorTest extends TestCase
{
    /** @var array */
    private $items;
    /** @var QueryIterator */
    private $iterator;
    /** @var \PDOStatement|\PHPUnit_Framework_MockObject_MockObject */
    private $dataIterator;
    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    private $query;

    protected function setUp()
    {
        $this->items = [
            ['foo'],
            ['bar'],
            ['baz']
        ];

        $this->dataIterator = new \ArrayIterator($this->items);
        $this->query = $this->createMock(Query::class);

        $this->iterator = new QueryIterator($this->dataIterator, $this->query);
    }

    public function testIterator()
    {
        $this->query
            ->expects($this->exactly(3))
            ->method('instanceFromObject')
            ->will($this->returnCallback(function (array $data) {
                return $data[0];
            }));

        $this->iterator->rewind();
        $this->assertEquals('foo', $this->iterator->current());

        $this->iterator->next();
        $this->assertEquals('bar', $this->iterator->current());

        $this->iterator->next();
        $this->assertEquals('baz', $this->iterator->current());
    }

    /**
     * @expectedException \Rorm\QueryException
     */
    public function testWillThrowExceptionOnRewindIfAlreadyUsed()
    {
        $this->iterator->next();
        $this->iterator->rewind();
    }
}
