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

for ($i = 0; $i < 3; $i++) {
    foreach ($SQUARES as $square) {
        // Upper Left
        /** @var Antenna $antenna */
        $antenna = $ANTENNAS->where('cell', null)->first();
        if (!$antenna) {
            break;
        }
        $cell = $MAP->getCell($square->upperLeft->x, $square->upperLeft->y);
        $antenna->cell = $cell;
        $cell->antenna = $antenna;

        // Upper Right
        /** @var Antenna $antenna */
        $antenna = $ANTENNAS->where('cell', null)->first();
        if (!$antenna) {
            break;
        }
        $cell = $MAP->getCell($square->upperRight->x, $square->upperRight->y);
        $antenna->cell = $cell;
        $cell->antenna = $antenna;

        // Lower Left
        /** @var Antenna $antenna */
        $antenna = $ANTENNAS->where('cell', null)->first();
        if (!$antenna) {
            break;
        }
        $cell = $MAP->getCell($square->lowerLeft->x, $square->lowerLeft->y);
        $antenna->cell = $cell;
        $cell->antenna = $antenna;

        // Lower Right
        /** @var Antenna $antenna */
        $antenna = $ANTENNAS->where('cell', null)->first();
        if (!$antenna) {
            break;
        }
        $cell = $MAP->getCell($square->lowerRight->x, $square->lowerRight->y);
        $antenna->cell = $cell;
        $cell->antenna = $antenna;
    }

    // TODO: Nuovi angoli
    $square->upperLeft = $MAP->getCell($square->upperLeft->x + floor($square->getWidth() / 3), floor($square->upperLeft->y - $square->getHeight() / 3));
    $square->upperRight = $MAP->getCell($square->upperRight->x + floor($square->getWidth() / 3), floor($square->upperRight->y - $square->getHeight() / 3));
    $square->lowerLeft = $MAP->getCell($square->lowerLeft->x + floor($square->getWidth() / 3), floor($square->lowerLeft->y - $square->getHeight() / 3));
    $square->lowerRight = $MAP->getCell($square->lowerRight->x + floor($square->getWidth() / 3), floor($square->lowerRight->y - $square->getHeight() / 3));

    if (!$antenna = $ANTENNAS->where('cell', null)->first()) {
        break;
    }
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
