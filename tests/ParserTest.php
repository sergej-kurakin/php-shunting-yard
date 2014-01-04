<?php

namespace tests;

use RR\Shunt\Parser;
use RR\Shunt\RuntimeError;

class ParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $equation
     * @param $expected
     *
     * @dataProvider simpleEquations
     */
    public function testParserWithSimpleEquations($equation, $expected)
    {
        $actual = Parser::parse($equation);

        $this->assertEquals($expected, $actual);
    }

    public function simpleEquations()
    {
        return array(
            array(
                '2+3',
                5.0,
            ),
            array(
                '2-3',
                -1.0,
            ),
            array(
                '2*3',
                6.0,
            ),
            array(
                '2/3',
                (2/3),
            ),
            array(
                '3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3',
                3.0001220703125,
            )
        );
    }

    /**
     * @expectedException \RR\Shunt\RuntimeError
     */
    public function testParenthesisThrowsError()
    {
        $equation = '()';

        Parser::parse($equation);
    }

}
