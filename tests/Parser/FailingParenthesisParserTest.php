<?php

namespace tests\Parser;

use RR\Shunt\Parser;
use RR\Shunt\RuntimeError;

class FailingParenthesisParserTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \RR\Shunt\RuntimeError
     */
    public function testParenthesisThrowsError()
    {
        $equation = '()';

        Parser::parse($equation);
    }

}
 