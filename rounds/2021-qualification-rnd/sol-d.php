<?php

use Utils\FileManager;
use Utils\Log;

$fileName = 'd';

include 'reader.php';

/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Developer[] $DEVELOPERS */
/** @var FileManager $fileManager */
/** @var Map $MAP */

/** @var Company[] $COMPANIES */
foreach ($COMPANIES as $C) {
    $sum = 0;
    foreach ($C->inDevelopers as $dev) {
        $sum += $dev->bonus;
    }
    $C->mediumBonus = $sum / count($C->inDevelopers);
}

$COMPANIES = collect($COMPANIES)->keyBy('id');
$COMPANIES = $COMPANIES->sortByDesc('mediumBonus');

// Punto di partenza della spirale
$currentCell = $MAP->map[1][0];

do {
    /** @var Company $company */
    $company = $COMPANIES->first();
    $currentCell->assignedTo = $company->inDevelopers->shift();
    $currentCell->assignedTo->posH = $currentCell->y;
    $currentCell->assignedTo->posW = $currentCell->x;

    /** @var Cell[] $freePJCells */
    $freePJCells = $MAP->getFreeNeighbours($currentCell, 'M');
    foreach ($freePJCells as $PJCell) {
        if ($company->inProjectManagers->count() == 0) {
            break;
        }
        $PJCell->assignedTo = $company->inProjectManagers->shift();
        $PJCell->assignedTo->posH = $PJCell->y;
        $PJCell->assignedTo->posW = $PJCell->x;
    }

    if ($company->inDevelopers->count() == 0) {
        $COMPANIES->forget($company->id);
    }

} while ($currentCell = $MAP->getFirstFreeNeighbour($currentCell, $currentCell->type));

Log::out('Output...');
foreach ($DEVELOPERS as $k => $dev) {
    $y = $dev->posH;
    $x = $dev->posW;
    if ($y && $x)
        $output .= $x . ' ' . $y . PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
foreach ($PROJECTMANAGERS as $pm) {
    $y = $pm->posH;
    $x = $pm->posW;
    if ($y && $x)
        $output .= $x . ' ' . $y . PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
$fileManager->outputV2($output, time());
