<?php

use Utils\DirUtils;

$fileName = 'e';

require_once './classes.php';

$grid = new Grid();

$cacheName = 'cache/' . $fileManager->getInputName();
DirUtils::makeDirOrCreate(dirname($cacheName));

$allPositions = [];
$inverse = [];

if (file_exists($cacheName)) {
    $cacheRows = explode("\n", file_get_contents($cacheName));
    foreach ($cacheRows as $rowId => $cacheRow) {
        $cacheRow = str_replace(';;', ';', $cacheRow);
        $allRow = explode(";", $cacheRow);
        list ($row, $col) = explode(":", $allRow[0]);
        $index = $row . '-' . $col;
        $aPosition = [
            'row' => $row,
            'col' => $col,
            'covered' => [],
        ];

        for ($i = 1; $i < count($allRow); $i++) {
            list($r, $c) = explode(':', $allRow[$i]);
            $aPosition['covered'][$r . '-' . $c] = true;
            $inverse[$r . '-' . $c][] = $index;
        }

        $aPosition['count'] = count($aPosition['covered']);

        $allPositions[$index] = $aPosition;
    }

    foreach ($inverse as $key => $row) {
        $inverse[$key] = array_unique($row);
    }
} else {
    $cacheFile = fopen('cache/' . $fileManager->getInputName(), 'w');

    $cacheRows = [];
    foreach ($grid->grid as $row => $gridRow) {
        foreach ($gridRow as $col => $cell) {
            if ($cell != '.')
                continue;
            $cacheRow = "$row:$col;";
            $coveredCells = $grid->getCoveredCells($row, $col);
            $coveredStrings = [];
            foreach ($coveredCells as $coveredCell) {
                $coveredStrings[] = $coveredCell[0] . ':' . $coveredCell[1];
            }
            $cacheRows[] = $cacheRow . ";" . implode(';', $coveredStrings);
        }
    }

    fwrite($cacheFile, implode("\n", $cacheRows));
    die("runna di nuovo scemo");
}


echo "INIZIO\n";
do {
    $top = collect($allPositions)->sortByDesc('count')->first();
    $row = $top['row'];
    $col = $top['col'];
    $index = $row . '-' . $col;
    unset($allPositions[$index]);
    foreach ($top['covered'] as $coveredRC => $inutile) {
        foreach ($inverse[$coveredRC] as $allPosIndex) {
            unset($allPositions[$allPosIndex]['covered'][$index]);
            $allPositions[$allPosIndex]['count'] = count($allPositions[$allPosIndex]['covered']);
        }
    }

    echo $grid->remainingBudget . ' / ' . $grid->budget . "\n";

    $result = $grid->placeRouter($top['row'], $top['col']);
} while ($result);

$grid->printSolution();
$grid->outputSolution();
