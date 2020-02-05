<?php

$fileName = 'b';

include_once('heating.php');

/** @var int $rowsCount */
/** @var int $columnsCount */
/** @var int $routerRadius */
/** @var int $backbonePrice */
/** @var int $routerPrice */
/** @var int $maxBudget */
/** @var int $backboneStartRow */
/** @var int $backboneStartColumn */
/** @var Cell[][] $map */

$routersPlaced = [];

function getBestRouterPosition($r, $c)
{
    global $map, $routerRadius;

    $maxCoverage = 0;
    $maxCoverageRouterPosition = [null, null];

    for ($rt = $r - $routerRadius; $rt <= $r + $routerRadius; $rt++) {
        for ($ct = $c - $routerRadius; $ct <= $c + $routerRadius; $ct++) {
            if (!$map[$rt][$ct]->isCovered && count($map[$rt][$ct]->coverableCells) > $maxCoverage) {
                $maxCoverage = count($map[$rt][$ct]->coverableCells);
                $maxCoverageRouterPosition = [$rt, $ct];
            }
        }
    }
    return $maxCoverage > 0 ? $maxCoverageRouterPosition : false;
}

function applyRouter($r, $c)
{
    global $routersPlaced, $map, $routerRadius;
    $routersPlaced[] = [$r, $c];
    $map[$r][$c]->hasRouter = true;
    foreach ($map[$r][$c]->coverableCells as $cell) {
        $map[$cell[0]][$cell[1]]->isCovered = true;
    }
    for ($rt = $r - $routerRadius; $rt <= $r + $routerRadius; $rt++) {
        for ($ct = $c - $routerRadius; $ct <= $c + $routerRadius; $ct++) {
            recalcRouterCoverableCells($rt, $ct);
        }
    }
}

for ($r = 0; $r <= $rowsCount; $r++) {
    for ($c = 0; $c <= $columnsCount; $c++) {
        if ($map[$r][$c]->isTarget && !$map[$r][$c]->isCovered) {
            $bestRouterPosition = getBestRouterPosition($r, $c);
            if($bestRouterPosition !== false) {
                echo "Placed router @ $r/$rowsCount $c\n";
                applyRouter($bestRouterPosition[0], $bestRouterPosition[1]);
                plot('test1');
            }
        }
    }
}


