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

use RR\Shunt\Exception\RuntimeError;
use RR\Shunt\Exception\ParseError;

class Parser
{
    const ST_1 = 1, // wartet auf operand oder unäre vorzeichen
          ST_2 = 2; // wartet auf operator

    protected $scanner, $state = self::ST_1;
    protected $queue, $stack;

    public function __construct(Scanner $scanner)
    {
        $this->scanner = $scanner;

        // alloc
        $this->queue = array();
        $this->stack = array();

        // queue erzeugen
        while (($t = $this->scanner->next()) !== false) {
            $this->handle($t);
        }

        // When there are no more tokens to read:
        // While there are still operator tokens in the stack:
        while ($t = array_pop($this->stack)) {
            if ($t->type === Token::T_POPEN || $t->type === Token::T_PCLOSE)
                throw new ParseError('parser fehler: fehlerhafte verschachtelung von `(` und `)`');

            $this->queue[] = $t;
        }
    }

    public function reduce(Context $ctx)
    {
        $this->stack = array();
        $len = 0;

        // While there are input tokens left
        // Read the next token from input.
        while ($t = array_shift($this->queue)) {
            switch ($t->type) {
                case Token::T_NUMBER:
                case Token::T_IDENT:
                    // wert einer konstanten ermitteln
                    if ($t->type === Token::T_IDENT)
                        $t = new Token(Token::T_NUMBER, $ctx->cs($t->value));

                    // If the token is a value or identifier
                    // Push it onto the stack.
                    $this->stack[] = $t;
                    ++$len;
                    break;

                case Token::T_PLUS:
                case Token::T_MINUS:
                case Token::T_UNARY_PLUS:
                case Token::T_UNARY_MINUS:
                case Token::T_TIMES:
                case Token::T_DIV:
                case Token::T_MOD:
                case Token::T_POW:
                case Token::T_NOT:
                    // It is known a priori that the operator takes n arguments.
                    $na = $this->argc($t);

                    // If there are fewer than n values on the stack
                    if ($len < $na)
                        throw new RuntimeError('laufzeit fehler: zu wenig paramter für operator "' . $t->value . '" (' . $na . ' -> ' . $len . ')');

                    $rhs = array_pop($this->stack);
                    $lhs = null;

                    if ($na > 1)
                        $lhs = array_pop($this->stack);

                    // if ($lhs) print "{$lhs->value} {$t->value} {$rhs->value}\n";
                    // else print "{$t->value} {$rhs->value}\n";

                    $len -= $na - 1;

                    // Push the returned results, if any, back onto the stack.
                    $this->stack[] = new Token(Token::T_NUMBER, $this->op($t->type, $lhs, $rhs));
                    break;

                case Token::T_FUNCTION:
                    // function
                    $argc = $t->argc;
                    $argv = array();

                    $len -= $argc - 1;

                    for (; $argc > 0; --$argc)
                        array_unshift($argv, array_pop($this->stack)->value);

                    // Push the returned results, if any, back onto the stack.
                    $this->stack[] = new Token(Token::T_NUMBER, $ctx->fn($t->value, $argv));
                    break;

                default:
                    throw new RuntimeError('laufzeit fehler: unerwarteter token `' . $t->value . '`');
            }
        }

        // If there is only one value in the stack
        // That value is the result of the calculation.
        if (count($this->stack) === 1)
            return array_pop($this->stack)->value;

        // If there are more values in the stack
        // (Error) The user input has too many values.
        throw new RuntimeError('laufzeit fehler: zu viele werte im stack');
    }

    protected function op($op, $lhs, $rhs)
    {
        if ($lhs !== null) {
            $lhs = $lhs->value;
            $rhs = $rhs->value;

            switch ($op) {
                case Token::T_PLUS:
                    return $lhs + $rhs;

                case Token::T_MINUS:
                    return $lhs - $rhs;

                case Token::T_TIMES:
                    return $lhs * $rhs;

                case Token::T_DIV:
                    if ($rhs === 0.)
                        throw new RuntimeError('laufzeit fehler: teilung durch 0');

                    return $lhs / $rhs;

                case Token::T_MOD:
                    if ($rhs === 0.)
                        throw new RuntimeError('laufzeit fehler: rest-teilung durch 0');

                    // php (bzw. c) kann hier nur mit ganzzahlen umgehen
                    return (float)$lhs % $rhs;

                case Token::T_POW:
                    return (float)pow($lhs, $rhs);
            }

            // throw?
            return 0;
        }

        switch ($op) {
            case Token::T_NOT:
                return (float)!$rhs->value;

            case Token::T_UNARY_MINUS:
                return -$rhs->value;

            case Token::T_UNARY_PLUS:
                return +$rhs->value;
        }
    }

    protected function argc(Token $t)
    {
        switch ($t->type) {
            case Token::T_PLUS:
            case Token::T_MINUS:
            case Token::T_TIMES:
            case Token::T_DIV:
            case Token::T_MOD:
            case Token::T_POW:
                return 2;
        }

        return 1;
    }

    public function dump($str = false)
    {
        if ($str === false) {
            print_r($this->queue);

            return;
        }

        $res = array();

        foreach ($this->queue as $t) {
            $val = $t->value;

            switch ($t->type) {
                case Token::T_UNARY_MINUS:
                case Token::T_UNARY_PLUS:
                    $val = 'unary' . $val;
                    break;
            }

            $res[] = $val;
        }

        print implode(' ', $res);
    }

    protected function fargs($fn)
    {
        $this->handle($this->scanner->next()); // '('

        $argc = 0;
        $next = $this->scanner->peek();

        if ($next && $next->type !== Token::T_PCLOSE) {
            $argc = 1;

            while ($t = $this->scanner->next()) {
                $this->handle($t);

                if ($t->type === Token::T_PCLOSE)
                    break;

                if ($t->type === Token::T_COMMA)
                    ++$argc;
            }
        }

        $fn->argc = $argc;
    }

    protected function handle(Token $t)
    {
        switch ($t->type) {
            case Token::T_NUMBER:
            case Token::T_IDENT:
                // If the token is a number (identifier), then add it to the output queue.
                $this->queue[] = $t;
                $this->state = self::ST_2;
                break;

            case Token::T_FUNCTION:
                // If the token is a function token, then push it onto the stack.
                $this->stack[] = $t;
                $this->fargs($t);
                break;

            case Token::T_COMMA:
                // If the token is a function argument separator (e.g., a comma):

                $pe = false;

                while ($t = end($this->stack)) {
                    if ($t->type === Token::T_POPEN) {
                        $pe = true;
                        break;
                    }

                    // Until the token at the top of the stack is a left parenthesis,
                    // pop operators off the stack onto the output queue.
                    $this->queue[] = array_pop($this->stack);
                }

                // If no left parentheses are encountered, either the separator was misplaced
                // or parentheses were mismatched.
                if ($pe !== true)
                    throw new ParseError('parser fehler: vermisster token `(` oder fehlplazierter token `,`');

                break;

            // If the token is an operator, op1, then:
            case Token::T_PLUS:
            case Token::T_MINUS:
            case Token::T_UNARY_PLUS:
            case Token::T_UNARY_MINUS:
            case Token::T_TIMES:
            case Token::T_DIV:
            case Token::T_MOD:
            case Token::T_POW:
            case Token::T_NOT:
                while (!empty($this->stack)) {
                    $s = end($this->stack);

                    // While there is an operator token, o2, at the top of the stack
                    // op1 is left-associative and its precedence is less than or equal to that of op2,
                    // or op1 has precedence less than that of op2,
                    // Let + and ^ be right associative.
                    // Correct transformation from 1^2+3 is 12^3+
                    // The differing operator priority decides pop / push
                    // If 2 operators have equal priority then associativity decides.
                    switch ($s->type) {
                        default:
                            break 2;

                        case Token::T_PLUS:
                        case Token::T_MINUS:
                        case Token::T_UNARY_PLUS:
                        case Token::T_UNARY_MINUS:
                        case Token::T_TIMES:
                        case Token::T_DIV:
                        case Token::T_MOD:
                        case Token::T_POW:
                        case Token::T_NOT:
                            $p1 = $this->preced($t);
                            $p2 = $this->preced($s);

                            if (!(($this->assoc($t) === 1 && ($p1 <= $p2)) || ($p1 < $p2)))
                                break 2;

                            // Pop o2 off the stack, onto the output queue;
                            $this->queue[] = array_pop($this->stack);
                    }
                }

                // push op1 onto the stack.
                $this->stack[] = $t;
                $this->state = self::ST_1;
                break;

            case Token::T_POPEN:
                // If the token is a left parenthesis, then push it onto the stack.
                $this->stack[] = $t;
                $this->state = self::ST_1;
                break;

            // If the token is a right parenthesis:
            case Token::T_PCLOSE:
                $pe = false;

                // Until the token at the top of the stack is a left parenthesis,
                // pop operators off the stack onto the output queue
                while ($t = array_pop($this->stack)) {
                    if ($t->type === Token::T_POPEN) {
                        // Pop the left parenthesis from the stack, but not onto the output queue.
                        $pe = true;
                        break;
                    }

                    $this->queue[] = $t;
                }

                // If the stack runs out without finding a left parenthesis, then there are mismatched parentheses.
                if ($pe !== true)
                    throw new ParseError('parser fehler: unerwarteter token `)`');

                // If the token at the top of the stack is a function token, pop it onto the output queue.
                if (($t = end($this->stack)) && $t->type === Token::T_FUNCTION)
                    $this->queue[] = array_pop($this->stack);

                $this->state = self::ST_2;
                break;

            default:
                throw new ParseError('parser fehler: unbekannter token "' . $t->value . '"');
        }
    }

    protected function assoc(Token $t)
    {
        switch ($t->type) {
            case Token::T_TIMES:
            case Token::T_DIV:
            case Token::T_MOD:

            case Token::T_PLUS:
            case Token::T_MINUS:
                return 1; //ltr

            case Token::T_NOT:
            case Token::T_UNARY_PLUS:
            case Token::T_UNARY_MINUS:

            case Token::T_POW:
                return 2; //rtl
        }

        // ggf. erweitern :-)
        return 0; //nassoc
    }

    protected function preced(Token $t)
    {
        switch ($t->type) {
            case Token::T_NOT:
            case Token::T_UNARY_PLUS:
            case Token::T_UNARY_MINUS:
                return 4;

            case Token::T_POW:
                return 3;

            case Token::T_TIMES:
            case Token::T_DIV:
            case Token::T_MOD:
                return 2;

            case Token::T_PLUS:
            case Token::T_MINUS:
                return 1;
        }

        return 0;
    }

    public static function parse($term, Context $ctx = null)
    {
        $obj = new self(new Scanner($term));

        return $obj
            ->reduce($ctx ? : new Context);
    }
}
