<?php

namespace tests\Context;

use RR\Shunt\Context;
use RR\Shunt\Exception\RuntimeError;
use Exception;

class ConstantDefinitionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $value
     * @param $expected
     *
     * @dataProvider constantsProvider
     */
    public function testConstantDefinitionAndCall($value, $expected)
    {
        $context = new Context();

        $context->def('const', $value);

        $actual = $context->cs('const');

        $this->assertEquals($expected, $actual);

    }

    public function constantsProvider()
    {
        return array(
            array(
                3,
                3.0,
            ),
            array(
                '3',
                3.0,
            ),
            array(
                3.2,
                3.2,
            ),
            array(
                '3.2',
                3.2,
            ),
            array(
                0.2,
                0.2,
            ),
            array(
                '0.2',
                0.2,
            ),
            array(
                '.2',
                0.2,
            ),
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testNonNumericConstantDefinition()
    {
        $context = new Context();

        $context->def('const', 'Just a String That Causes Error #$#$%#@');
    }

    /**
     * @expectedException \RR\Shunt\Exception\RuntimeError
     */
    public function testCallNotsetConstantCausesException()
    {
        $context = new Context();

        $context->cs('notdefinedfunction');
    }

}
