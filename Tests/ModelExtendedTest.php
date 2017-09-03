<?php
/**
 * @author: remy
 */
declare(strict_types=1);

namespace RormTest;

use PHPUnit\Framework\TestCase;
use Rorm\Query;
use Rorm\QueryBuilder;
use RormTest\Model\GuessedName;
use RormTest\Model\TestBasic;

/**
 * Class ModelExtendedTest
 * @package RormTest
 */
class ModelExtendedTest extends TestCase
{
    public function testTableName()
    {
        $this->assertEquals('test_basic', TestBasic::getTable());
        $this->assertEquals('rormtest_model_guessedname', GuessedName::getTable());
    }

    public function testQuery()
    {
        $query = TestBasic::query();
        $this->assertInstanceOf(QueryBuilder::class, $query);
        $this->assertEquals(TestBasic::class, $query->getClass());
        $this->assertEquals(TestBasic::getTable(), $query->getTable());
    }

    public function testCustomQuery()
    {
        // basic
        $sqlBasic = 'SELECT id, name FROM test_basic ORDER BY modified';
        $queryBasic = TestBasic::customQuery($sqlBasic);
        $this->assertInstanceOf(Query::class, $queryBasic);
        $this->assertNotInstanceOf(QueryBuilder::class, $queryBasic);
        $this->assertEquals(TestBasic::class, $queryBasic->getClass());
        $this->assertEquals($sqlBasic, $queryBasic->getQuery());
        $this->assertEmpty($queryBasic->getParams());

        // params
        $sqlParams = 'SELECT id, name FROM test_basic WHERE active = ? ORDER BY modified';
        $params = [75];
        $queryParam = TestBasic::customQuery($sqlParams, $params);
        $this->assertEquals($sqlParams, $queryParam->getQuery());
        $this->assertEquals($params, $queryParam->getParams());
    }
}
