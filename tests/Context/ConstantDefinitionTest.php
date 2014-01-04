<?php

namespace tests\Context;

use RR\Shunt\Context;
use RR\Shunt\Exception\RuntimeError;
use Exception;

class ConstantDefinitionTest extends \PHPUnit_Framework_TestCase
{

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
