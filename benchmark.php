#!/usr/bin/php
<?php

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}

use RR\Shunt\Parser;

function benchmark($term)
{
    print "$term\n";

    $iterations = 10000;

    // native calculations
    $a = microtime(true);

    for ($i = 0; $i < $iterations; ++$i) {
        $r = eval("return $term;");
    }

    $totalExecutionTime = microtime(true) - $a;
    print "native = $r : " . (round($totalExecutionTime, 6)) . "s. ".round(($totalExecutionTime/$iterations), 10)."s. per operation \n";

    // calculations using parser
    $a = microtime(true);

    for ($i = 0; $i < $iterations; ++$i) {
        $r = Parser::parse($term);
    }

    $totalExecutionTime = microtime(true) - $a;
    print "parser = $r : " . (round($totalExecutionTime, 6)) . "s. ".round(($totalExecutionTime/$iterations), 10)."s. per operation\n\n";
}

benchmark('1+1');
benchmark('-2*4/2');
benchmark('3+4*2/(1-5)*2*3');
benchmark('3+4*2/(1-5)*2*3+3+4*2/(1-5)*2*3');
benchmark('3+4*2/(1-5)*2*3+3+4*2/(1-5)*2*3*3+4*2/(1-5)*2*3+3+4*2/(1-5)*2*3');

benchmark('3.2+4.1*2.5/(1.1-5.4)*2.7*3.9+3.1+4.4*2.4/(1.1-5.5)*2.4*3.9*3.7+4.8*'
  . '2.3/(1.7-5.9)*2.4*3.1+3.2+4.6*2.8/(1.5-5.6)*2.1*3.9*3.2+4.1*2.5/(1.1-5.4)*'
  . '2.7*3.9+3.1+4.4*2.4/(1.1-5.5)*2.4*3.9*3.7+4.8*2.3/(1.7-5.9)*2.4*3.1+3.2+4.'
  . '6*2.8/(1.5-5.6)*2.1*3.9+6*2.8/(1.5-5.6)*2.1*3.9-6*2.8/(1.5-5.6)*2.1*3.9+1+'
  . '2.3/(1.7-5.9)*2.4*3.1+3.2+4.6*2.8/(1.5-5.6)*2.1*3.9*3.2+4.1*2.5/(1.1-5.4)*'
  . '2.7*3.9+3.1+4.4*2.4/(1.1-5.5)*2.4*3.9*3.7+4.8*2.3/(1.7-5.9)*2.4*3.1+3.2+4.'
  . '6*2.8/(1.5-5.6)*2.1*3.9+6*2.8/(1.5-5.6)*2.1*3.9-6*2.8/(1.5-5.6)*2.1*3.9+1');
