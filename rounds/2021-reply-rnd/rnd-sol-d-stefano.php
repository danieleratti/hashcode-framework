<?php

use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'd';

include 'rnd-reader.php';

/** @var Collection|Building[] $BUILDINGS */
/** @var Collection|Antenna[] $ANTENNAS */
/** @var MAP $MAP */
/** @var FileManager $fileManager */
/** @var Collection|Square[] $SQUARES */

// TODO: Ordinare square per area

// TODO: Ordinare le antenne per ??

// Mette negli angoli ottimizzando la distanza
foreach ($SQUARES as $square) {
    // Upper Left
    /** @var Antenna $antenna */
    $antenna = $ANTENNAS->where('cell', null)->first();
    if (!$antenna) {
        break;
    }
    $x = $square->upperLeft->x + floor(sqrt($antenna->range));
    $y = $square->upperLeft->y - floor(sqrt($antenna->range));
    $cell = $MAP->getCell($x, $y);
    $antenna->cell = $cell;
    $cell->antenna = $antenna;

    // Upper Right
    /** @var Antenna $antenna */
    $antenna = $ANTENNAS->where('cell', null)->first();
    if (!$antenna) {
        break;
    }
    $x = $square->upperRight->x - floor(sqrt($antenna->range));
    $y = $square->upperRight->y - floor(sqrt($antenna->range));
    $cell = $MAP->getCell($x, $y);
    $antenna->cell = $cell;
    $cell->antenna = $antenna;

    // Lower Left
    /** @var Antenna $antenna */
    $antenna = $ANTENNAS->where('cell', null)->first();
    if (!$antenna) {
        break;
    }
    $x = $square->lowerLeft->x + floor(sqrt($antenna->range));
    $y = $square->lowerLeft->y + floor(sqrt($antenna->range));
    $cell = $MAP->getCell($x, $y);
    $antenna->cell = $cell;
    $cell->antenna = $antenna;

    // Lower Right
    /** @var Antenna $antenna */
    $antenna = $ANTENNAS->where('cell', null)->first();
    if (!$antenna) {
        break;
    }
    $x = $square->lowerRight->x - floor(sqrt($antenna->range));
    $y = $square->lowerRight->y + floor(sqrt($antenna->range));
    $cell = $MAP->getCell($x, $y);
    $antenna->cell = $cell;
    $cell->antenna = $antenna;
}


Log::out('Output...');
$assignedAntennas = $sortedAntennas->filter(function ($item) {
    return $item->cell !== null;
});
$output = $assignedAntennas->count() . PHP_EOL;
/** @var Antenna $antenna */
foreach ($assignedAntennas as $k => $antenna) {
    $id = $antenna->id;
    $y = $antenna->cell->y;
    $x = $antenna->cell->x;
    $output .= $id . ' ' . $x . ' ' . $y . PHP_EOL;
}

$fileManager->outputV2($output, time());
