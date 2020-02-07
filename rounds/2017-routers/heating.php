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


// Pre-heating (serialized)
function existsRectBetweenPoints($r1, $c1, $r2, $c2)
{
    global $map;

    if ($r2 < $r1) {
        $rt = $r1;
        $r1 = $r2;
        $r2 = $rt;
    }
    if ($c2 < $c1) {
        $ct = $c1;
        $c1 = $c2;
        $c2 = $ct;
    }

    for ($r = $r1; $r <= $r2; $r++)
        for ($c = $c1; $c <= $c2; $c++)
            if ($map[$r][$c]->isWall)
                return false;
    return true;
}

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
                if (!$map[$cr][$cc]->isCovered)
                    $cells[] = [$cr, $cc];
            }
        }
    }
    $map[$r][$c]->coverableCells = $cells;
}

function recalcRouterCoverableCells($r, $c)
{
    global $map;
    if ($map[$r][$c]->isTarget) {
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
echo "Tempo: " . ($t2 - $t1) . "\n";
