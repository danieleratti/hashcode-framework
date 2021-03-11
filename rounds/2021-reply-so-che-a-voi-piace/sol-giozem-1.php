<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'c';
Cerberus::runClient(['fileName' => $fileName]);
Autoupload::init();
include 'reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antennas */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$SCORE = 0;
$numPlacedAntennas = 0;
$unavailableCoords = [];

/* FUNCTIONS */

function findAdHocBuilding($antenna) {
    global $buildings, $unavailableCoords;

    $bestBuilding = null;
    $bestScore = 0;
    foreach ($buildings as $building) {
        $score = $antenna->score($building, $building->r, $building->c);

        $coords = [$building->r, $building->c];
        if(!in_array($coords, $unavailableCoords)) {
            if($bestBuilding == null || $score > $bestScore) {
                $bestBuilding = $building;
                $bestScore = $score;
            }
        }
    }

    return $bestBuilding;
}

/* ALGO */

foreach ($antennas as $antenna) {
    if($antenna->range == 0) {
        $remaining = count($antennas) - $numPlacedAntennas;
        Log::out("Placing antenna ($antenna->id), placed $numPlacedAntennas, remaining $remaining");
        $building = findAdHocBuilding($antenna);
        $antenna->r = $building->r;
        $antenna->c = $building->c;
        $unavailableCoords[] = [$antenna->r, $antenna->c];
        $numPlacedAntennas++;
    }
}


/* SCORING & OUTPUT */
$output = $numPlacedAntennas . PHP_EOL;
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
    }
}

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'score_' . $SCORE);
