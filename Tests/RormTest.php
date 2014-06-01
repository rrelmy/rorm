<?php
/**
 * @author: remy
 */
namespace RormTest;

use PHPUnit_Framework_TestCase;
use Rorm\Rorm;

/**
 * Class RormTest
 */
class RormTest extends PHPUnit_Framework_TestCase
{

    public function testSetDatabase()
    {
        $db = Rorm::$db;
        Rorm::setDatabase($db);
        $this->assertEquals($db, Rorm::$db);
    }

    /**
     * @return array
     */
    public function providerQuoteIdentifier()
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
     * @dataProvider providerQuoteIdentifier
     */
    public function testQuoteIdentifier($value, $expected)
    {
        $this->assertEquals($expected, Rorm::quoteIdentifier($value));
    }
}
