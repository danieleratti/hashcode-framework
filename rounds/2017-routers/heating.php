<?php

include_once('reader.php');

/** @var int $rowsCount */
/** @var int $columnsCount */
/** @var int $routerRadius */
/** @var int $backbonePrice */
/** @var int $routerPrice */
/** @var int $maxBudget */
/** @var int $backboneStartRow */
/** @var int $backboneStartColumn */
/** @var Cell[][] $map */


// Delta matrix population
$_deltaMatrix = [];
for ($i = 1; $i <= $routerRadius; $i++) {
    for ($k = 0; $k <= $routerRadius; $k++) {
        $_deltaMatrix[0][$i][$k] = [
            $i,
            $k,
        ];
    }
}
for ($i = 0; $i <= $routerRadius; $i++) {
    for ($k = 1; $k <= $routerRadius; $k++) {
        $_deltaMatrix[1][$i][$k] = [
            $i,
            -$k,
        ];
    }
}
for ($i = 1; $i <= $routerRadius; $i++) {
    for ($k = 0; $k <= $routerRadius; $k++) {
        $_deltaMatrix[2][$i][$k] = [
            -$i,
            -$k,
        ];
    }
}
for ($i = 0; $i <= $routerRadius; $i++) {
    for ($k = 1; $k <= $routerRadius; $k++) {
        $_deltaMatrix[3][$i][$k] = [
            -$i,
            $k,
        ];
    }
}
/*
echo "\n";
foreach ($_deltaMatrix as $deltaQuadrant) {
    foreach ($deltaQuadrant as $deltaRow) {
        foreach ($deltaRow as $deltaCell) {
            echo "({$deltaCell[0]},{$deltaCell[1]}) ";
        }
        echo "\n";
    }
    echo "\n\n";
}
*/

function calculateCoverableCells($r, $c)
{
    global $map, $rowsCount, $columnsCount, $_deltaMatrix;
    $cells = [
        [$r, $c],
    ];
    foreach ($_deltaMatrix as $deltaQuadrant) {
        foreach ($deltaQuadrant as $deltaRow) {
            foreach ($deltaRow as $deltaCell) {
                $cr = $r + $deltaCell[0];
                $cc = $c + $deltaCell[1];
                if ($map[$cr][$cc]->isWall || $cr < 0 || $cr >= $rowsCount || $cc < 0 || $cc >= $columnsCount) {
                    break;
                }
                if ($map[$cr][$cc]->isTarget && !$map[$cr][$cc]->isCovered)
                    $cells[] = [$cr, $cc];
            }
        }
    }
    $map[$r][$c]->coverableCells = $cells;
}

function recalcRouterCoverableCells($r, $c)
{
    global $map;
    if ($map[$r][$c]->isTarget /*&& !$map[$r][$c]->isCovered*/) {
        $map[$r][$c]->coverableCells = [];
        calculateCoverableCells($r, $c);
    }
}

$t1 = microtime(true);
for ($r = 0; $r <= $rowsCount; $r++) {
    echo "Heating $r / $rowsCount\n";
    for ($c = 0; $c <= $columnsCount; $c++) {
        recalcRouterCoverableCells($r, $c);
    }
}
$t2 = microtime(true);
echo "Tempo heating: " . ($t2 - $t1) . "\n";
