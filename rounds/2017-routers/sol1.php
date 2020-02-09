<?php

$fileName = 'e';

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
$backbonesPlaced = [];
$targetsCovered = [];
$budget = $maxBudget;

function getBestRouterPosition($r, $c)
{
    global $map, $routerRadius;

    $maxCoverage = 0;
    $maxCoverageRouterPosition = [null, null];

    for ($rt = $r - $routerRadius; $rt <= $r + $routerRadius; $rt++) {
        for ($ct = $c - $routerRadius; $ct <= $c + $routerRadius; $ct++) {
            if (!$map[$rt][$ct]->isCovered && count($map[$rt][$ct]->coverableCells) > $maxCoverage) {
                if (in_array([$r, $c], $map[$rt][$ct]->coverableCells)) {
                    $maxCoverage = count($map[$rt][$ct]->coverableCells);
                    $maxCoverageRouterPosition = [$rt, $ct];
                }
            }
        }
    }
    return $maxCoverage > 0 ? $maxCoverageRouterPosition : false;
}

function applyRouter($r, $c)
{
    global $routersPlaced, $map, $routerRadius, $budget, $routerPrice, $targetsCovered;
    $routersPlaced[] = [$r, $c];
    $budget -= $routerPrice;

    $map[$r][$c]->hasRouter = true;
    foreach ($map[$r][$c]->coverableCells as $cell) {
        [$rt, $ct] = $cell;
        if (!$map[$rt][$ct]->isCovered) {
            $map[$rt][$ct]->isCovered = true;
            $map[$r][$c]->coveredCells[] = [$rt, $ct];
            //$targetsCovered[] = [$rt, $ct];
        }
    }
    $map[$r][$c]->coveredCellsCount = count($map[$r][$c]->coveredCells);
    for ($rt = $r - $routerRadius; $rt <= $r + $routerRadius; $rt++) {
        for ($ct = $c - $routerRadius; $ct <= $c + $routerRadius; $ct++) {
            recalcRouterCoverableCells($rt, $ct);
        }
    }
}

function applyBackbone($r, $c)
{
    global $backbonesPlaced, $budget, $backbonePrice, $map;
    $backbonesPlaced[] = [$r, $c];
    $map[$r][$c]->hasBackbone = true;
    $budget -= $backbonePrice;
}

function removeLessConvenientRouters()
{
    global $budget, $routerPrice, $routersPlaced, $map;
    while ($budget < 0) {
        $routerCell = array_pop($routersPlaced);
        $budget += $routerPrice;
        $map[$routerCell[0]][$routerCell[1]]->hasRouter = false;
        foreach ($map[$routerCell[0]][$routerCell[1]]->coveredCells as $cell) {
            $map[$cell[0]][$cell[1]]->isCovered = false;
        }
        // Dovrei togliere anche la backbone inutile poi
    }
}

$t1 = microtime(true);

for ($r = 0; $r <= $rowsCount; $r++) {
    for ($c = 0; $c <= $columnsCount; $c++) {
        if ($map[$r][$c]->isTarget && !$map[$r][$c]->isCovered) {
            $bestRouterPosition = getBestRouterPosition($r, $c);
            if ($bestRouterPosition !== false) {
                echo "Placed router @ $r/$rowsCount $c\n";
                applyRouter($bestRouterPosition[0], $bestRouterPosition[1]);
                //plot('test1');
            }
        }
    }
}

$t2 = microtime(true);
echo "Tempo piazzamento router: " . ($t2 - $t1) . "\n";

// Ordine i router per celle coperte
usort($routersPlaced, function ($routerA, $routerB) {
    global $map;
    return $map[$routerA[0]][$routerA[1]]->coveredCellsCount < $map[$routerB[0]][$routerB[1]]->coveredCellsCount;
});
removeLessConvenientRouters();

// Piazzo le backbone
$placedBackbones = [
    [$backboneStartRow, $backboneStartColumn],
];
for ($radius = 1; $radius <= max($rowsCount, $columnsCount); $radius++) {
    $multipliers = [
        [-1, 0],
        [0, 1],
        [1, 0],
        [0, -1],
    ];
    foreach ($multipliers as $m) {
        for ($i = -$radius; $i < $radius; $i++) {
            $r = $backboneStartRow + $radius * $m[0] + $i * ($m[0] === 0 ? 1 : 0);
            $c = $backboneStartColumn + $radius * $m[1] + $i * ($m[1] === 0 ? 1 : 0);
            if (isset($map[$r]) && isset($map[$r][$c]) && $map[$r][$c]->hasRouter) {
                // Per questa cella cerco la backbone più vicina
                $minDist = PHP_INT_MAX;
                $bestCell = null;
                foreach ($placedBackbones as $b) {
                    $dist = max(abs($r - $b[0]), abs($c - $b[1]));
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestCell = $b;
                    }
                }
                if (!$bestCell) {
                    die("C'è un problema.");
                }
                // Costruisco un path verso la best cell
                while ($r != $bestCell[0] || $c != $bestCell[1]) {
                    $placedBackbones[] = [$r, $c];
                    applyBackbone($r, $c);
                    //echo "Backbone in $r,$c\n";
                    if ($r > $bestCell[0]) $r--;
                    elseif ($r < $bestCell[0]) $r++;
                    if ($c > $bestCell[1]) $c--;
                    elseif ($c < $bestCell[1]) $c++;
                }
            }
        }
    }
}

plot('test1');

$targetsCoveredCount = 0;
foreach ($routersPlaced as $routerCell) {
    $targetsCoveredCount += $map[$routerCell[0]][$routerCell[1]]->coveredCellsCount;
}

$score = $targetsCoveredCount * 1000 + $budget;
echo "\nPunteggio = $score\n";
echo "Budget rim = $budget\n";

/*
$output = "";
$output .= count($backbonesPlaced) . "\n";
foreach ($backbonesPlaced as $b) {
    $output .= "{$b[0]} {$b[1]}\n";
}
$output .= count($routersPlaced) . "\n";
foreach ($routersPlaced as $r) {
    $output .= "{$r[0]} {$r[1]}\n";
}
echo "\n\n\n";
echo $output;
*/
