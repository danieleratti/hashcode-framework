<?php

$fileName = 'b';

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

$targets = 0;

foreach ($map as $rows) {
    foreach ($rows as $cell) {
        $targets += $cell->isTarget ? 1 : 0;
    }
}

$routersNeeded = $targets / pow($routerRadius * 2 + 1, 2);
echo "File = $fileName\n";
echo "Budget = $maxBudget\n";
echo "Targets = $targets\n";
echo "Theoricals routers needed = " . $routersNeeded . " (price = " . ($routersNeeded * $routerPrice) . ")\n";

