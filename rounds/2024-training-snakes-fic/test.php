<?php

$t1 = microtime(true);

for ($i = 0; $i < 10000000; $i++) {
    $k = ($i + 1) * 3 / 47;
}

$t2 = microtime(true);

echo $t2 - $t1;