<?php

namespace tests\Context;

use RR\Shunt\Parser;
use RR\Shunt\Context;
use RR\Shunt\RuntimeError;
use Exception;

class FunctionDefinitionTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Parser::parse('2+2');
    }

    public function testFunctionDefinitionAndCall()
    {
        $context = new Context();

        $context->def('func', function ($param1) {
            return $param1;
        });

        $actual = $context->fn('func', array(3));

        $this->assertEquals(3.0, $actual);

    }

    public function testSystemFunctionDefinition()
    {
        $context = new Context();

        $context->def('abs');

        $actual = $context->fn('abs', array(-3));

        $this->assertEquals(3.0, $actual);

    }

    /**
     * @expectedException \Exception
     */
    public function testNonCallableFunctionDefinition()
    {
        $context = new Context();

        $context->def('abs', 'Just a String That Causese Error #$#$%#@');
    }

    /**
     * @expectedException \RR\Shunt\RuntimeError
     */
    public function testCallNotsetFunctionCausesException()
    {
        $context = new Context();

        $context->fn('notdefinedfunction', array(-3));
    }

}
