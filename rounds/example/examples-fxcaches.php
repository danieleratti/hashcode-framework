<?php

use Utils\Stopwatch;

require_once '../../bootstrap.php';

$nTests = 100000;
$percCachable = 0.75;
$uSecLengthPerCall = 2;

function fxNoCache($a, $b)
{
    global $uSecLengthPerCall;
    usleep($uSecLengthPerCall);
    return 1;
    //return sqrt($a * $a + $b * $b);
}

function fxCached($a, $b)
{
    global $cache_fxCached;
    if(isset($cache_fxCached[$a][$b]))
        return $cache_fxCached[$a][$b];
    $ret = fxNoCache($a, $b);
    $cache_fxCached[$a][$b] = $ret;
    return $ret;
}

$testInputs = [];

for ($i = 0; $i < $nTests; $i++)
    $testInputs[] = [rand(0, $nTests * (1 - $percCachable))];

Stopwatch::tik('noCache');
for ($i = 0; $i < $nTests; $i++)
    fxNoCache($testInputs[$i][0], $testInputs[$i][1]);
Stopwatch::tok('noCache');

Stopwatch::tik('cached');
for ($i = 0; $i < $nTests; $i++)
    fxCached($testInputs[$i][0], $testInputs[$i][1]);
Stopwatch::tok('cached');


Stopwatch::print();
