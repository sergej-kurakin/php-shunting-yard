### PHP Shunting Yard Implementation

Master: [![Master Build Status](https://travis-ci.org/sergej-kurakin/php-shunting-yard.png?branch=master)](https://travis-ci.org/sergej-kurakin/php-shunting-yard)
Develop: [![Dev Build Status](https://travis-ci.org/sergej-kurakin/php-shunting-yard.png?branch=dev)](https://travis-ci.org/sergej-kurakin/php-shunting-yard)

## Example

Simple equation parsing
```php
use RR\Shunt\Parser;

$equation = '3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3';
$result = Parser::parse($equation);
echo $result; //3.0001220703125
```

Equation with constants and functions
```php
use RR\Shunt\Parser;
use RR\Shunt\Context;

$ctx = new Context();
$ctx->def('abs'); // wrapper for PHP "abs" function
$ctx->def('foo', 5); // constant "foo" with value "5"
$ctx->def('bar', function($a, $b) { return $a * $b; }); // define function

$equation = '3 + bar(4, 2) / (abs(-1) - foo) ^ 2 ^ 3';
$result = Parser::parse($equation, $ctx);
echo $result; //3.0001220703125
```

# MIT Licence

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.