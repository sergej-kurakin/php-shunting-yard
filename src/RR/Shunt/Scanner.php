<?php

/*!
 * PHP Shunting-yard Implementierung
 * Copyright 2012 - droptable <murdoc@raidrush.org>
 *
 * PHP 5.4 benötigt
 *
 * Referenz: <http://en.wikipedia.org/wiki/Shunting-yard_algorithm>
 *
 * ----------------------------------------------------------------
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without
 * limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to
 * whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * <http://opensource.org/licenses/mit-license.php>
 */

namespace RR\Shunt;

use RR\Shunt\Exception\SyntaxError;

class Scanner
{
    //                  operatoren          nummern               wörter                  leerzeichen
    const PATTERN = '/^([!,\+\-\*\/\^%\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

    const ERR_EMPTY = 'leerer fund! (endlosschleife) in der nähe von: `%s`',
        ERR_MATCH = 'syntax fehler in der nähe von `%s`';

    protected $tokens = array( 0 );

    protected $lookup = array(
        '+' => Token::T_PLUS,
        '-' => Token::T_MINUS,
        '/' => Token::T_DIV,
        '%' => Token::T_MOD,
        '^' => Token::T_POW,
        '*' => Token::T_TIMES,
        '(' => Token::T_POPEN,
        ')' => Token::T_PCLOSE,
        '!' => Token::T_NOT,
        ',' => Token::T_COMMA
    );

    public function __construct($input)
    {
        $prev = new Token(Token::T_OPERATOR, 'noop');

        while (trim($input) !== '') {
            if (!preg_match(self::PATTERN, $input, $match)) {
                // syntax fehler
                throw new SyntaxError(sprintf(self::ERR_MATCH, substr($input, 0, 10)));
            }

            if (empty($match[1]) && $match[1] !== '0') {
                // leerer fund -> endlosschleife vermeiden
                throw new SyntaxError(sprintf(self::ERR_EMPTY, substr($input, 0, 10)));
            }

            // aktuellen wert von input abziehen
            $input = substr($input, strlen($match[1]));

            if (($value = trim($match[1])) === '') {
                // leerzeichen ignorieren
                continue;
            }

            if (is_numeric($value)) {
                if ($prev->type === Token::T_PCLOSE)
                    $this->tokens[] = new Token(Token::T_TIMES, '*');

                $this->tokens[] = $prev = new Token(Token::T_NUMBER, (float) $value);
                continue;
            }

            switch ($type = isset($this->lookup[$value]) ? $this->lookup[$value] : Token::T_IDENT) {
                case Token::T_PLUS:
                    if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN) $type = Token::T_UNARY_PLUS;
                    break;

                case Token::T_MINUS:
                    if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN) $type = Token::T_UNARY_MINUS;
                    break;

                case Token::T_POPEN:
                    switch ($prev->type) {
                        case Token::T_IDENT:
                            $prev->type = Token::T_FUNCTION;
                            break;

                        case Token::T_NUMBER:
                        case Token::T_PCLOSE:
                            // erlaubt 2(2) -> 2 * 2 | (2)(2) -> 2 * 2
                            $this->tokens[] = new Token(Token::T_TIMES, '*');
                            break;
                    }

                    break;
            }

            $this->tokens[] = $prev = new Token($type, $value);
        }
    }

    public function curr() { return current($this->tokens); }
    public function next() { return next($this->tokens); }
    public function prev() { return prev($this->tokens); }
    public function dump() { print_r($this->tokens); }

    public function peek()
    {
        $v = next($this->tokens);
        prev($this->tokens);

        return $v;
    }
}
