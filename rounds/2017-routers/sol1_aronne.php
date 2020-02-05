<?php

use Utils\ArrayUtils;

$fileName = 'c';

require_once './classes.php';

$grid = new Grid();

$cellsPoints = [];
for ($r = 0; $r < $grid->gridRows; $r++) {
    for ($c = 0; $c < $grid->gridCols; $c++) {
        $cellsPoints[] = [
            'row' => $r,
            'col' => $c,
            'points' => count($grid->getCoveredCells($r, $c)),
        ];
    }
}

ArrayUtils::array_keysort($cellsPoints, 'points');
print_r($cellsPoints);

foreach ($cellsPoints as $point) {
    if (!$grid->placeRouter($point['row'], $point['col']))
        continue;
}

$grid->printSolution();
$grid->outputSolution();
