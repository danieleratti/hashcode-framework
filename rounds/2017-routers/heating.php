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

function recalcRouterCoverableCells($r, $c)
{
    global $map, $routerRadius;
    if ($map[$r][$c]->isVoid || $map[$r][$c]->isTarget) {
        $map[$r][$c]->coverableCells = [];
        
        for ($rt = $r - $routerRadius; $rt <= $r + $routerRadius; $rt++) {
            for ($ct = $c - $routerRadius; $ct <= $c + $routerRadius; $ct++) {
                if ($map[$rt][$ct]->isTarget && existsRectBetweenPoints($r, $c, $rt, $ct))
                    $map[$r][$c]->coverableCells[] = [$rt, $ct];
            }
        }

    }
}

for ($r = 0; $r <= $rowsCount; $r++) {
    echo "Heating $r / $rowsCount\n";
    for ($c = 0; $c <= $columnsCount; $c++) {
        recalcRouterCoverableCells($r, $c);
    }
}
