<?php

$fileName = 'a';
$outputName = 'a.out';

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

$output = file_get_contents(__DIR__ . '/output/' . $outputName);
$outputRows = explode("\n", $output);

// N
$n = (int)($outputRows[0]);
if ($n < 0 || $n >= $rowsCount * $columnsCount) {
    die("N ($n) non valido.");
}
array_shift($outputRows);

// Backbones
$backbonesCoverage = [];
for ($i = 0; $i < $n; $i++) {
    [$r, $c] = explode(' ', $outputRows[$i]);
    $backbonesCoverage["$r,$c"] = false;
}
coverBackbone($backboneStartRow, $backboneStartColumn, $backbonesCoverage);
foreach ($backbonesCoverage as $rc => $backboneCovered) {
    if ($backboneCovered === false) {
        die("La cella backbone in ($rc) non è collegata al resto della backbone.");
    }
}
array_splice($outputRows, 0, $n);

// M
$m = (int)($outputRows[0]);
if ($m < 0 || $m > $rowsCount * $columnsCount) {
    die("M ($m) non valido.");
}
array_shift($outputRows);

// Routers
$routers = [];
for ($i = 0; $i < $m; $i++) {
    [$r, $c] = explode(' ', $outputRows[$i]);
    if (!isRouterConnected($r, $c, $backbonesCoverage)) {
        die("Il router in ($r,$c) non è collegato alla backbone.");
    }
    if ($map[$r][$c]->isWall) {
        die("Il router in ($r,$c) è stato murato (è già stato arrestato).");
    }
}
array_splice($outputRows, 0, $m);

if ($n * $backbonePrice + $m * $routerPrice > $maxBudget) {
    die("Hai sforato il budget.");
}

// Calcolo lo score



// Functions

function coverBackbone($fromR, $fromC, &$backbonesCoverage)
{
    global $rowsCount, $columnsCount;
    $directions = [
        [-1, -1],
        [-1, 0],
        [-1, 1],
        [0, 1],
        [0, -1],
        [1, 0],
        [1, 1],
    ];
    foreach ($directions as $d) {
        $r = $fromR + $d[0];
        $c = $fromC + $d[1];
        if ($r >= 0 && $r < $rowsCount && $c >= 0 && $c < $columnsCount) {
            if (isset($backbonesCoverage["$r,$c"]) && $backbonesCoverage["$r,$c"] === false) {
                $backbonesCoverage["$r,$c"] = true;
                coverBackbone($r, $c, $backbonesCoverage);
            }
        }
    }
}

function isRouterConnected($r, $c, &$backbonesCoverage)
{
    $directions = [
        [0, 0],
        [-1, -1],
        [-1, 0],
        [-1, 1],
        [0, 1],
        [0, -1],
        [1, 0],
        [1, 1],
    ];
    foreach ($directions as $d) {
        $tr = $r + $d[0];
        $tc = $c + $d[1];
        if (isset($backbonesCoverage["$tr,$tc"])) {
            return true;
        }
    }
    return false;
}
