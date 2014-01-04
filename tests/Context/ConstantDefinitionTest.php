<?php

namespace tests\Context;

use RR\Shunt\Parser;
use RR\Shunt\Context;
use RR\Shunt\RuntimeError;
use Exception;

class ConstantDefinitionTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Parser::parse('2+2');
    }

    public function testConstantDefinitionAndCall()
    {
        $context = new Context();

        $context->def('const', 3);

        $actual = $context->cs('const');

        $this->assertEquals(3.0, $actual);

    }

    /**
     * @expectedException \Exception
     */
    public function testNonNumericConstantDefinition()
    {
        $context = new Context();

        $context->def('const', 'Just a String That Causese Error #$#$%#@');
    }

    /**
     * @expectedException \RR\Shunt\RuntimeError
     */
    public function testCallNotsetConstantCausesException()
    {
        $context = new Context();

        $context->cs('notdefinedfunction');
    }

}
