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
$placedAntennas = 0;

/* FUNCTIONS */
/** @var Building $a */
/** @var Building $b */
function compareBuilding($a, $b) {
    return $a->speedWeight > $b->speedWeight;
}

function compareAntenna($a, $b) {
    return $a->speed > $b->speed;
}


/* ALGO */
// Ordino edifici per speed alta e antenna per speed alta.
usort($buildings, "compareBuilding");
usort($antennas, "compareAntenna");

foreach ($buildings as $build) {    
    $antennas[$placedAntennas]->r = $build->r;
    $antennas[$placedAntennas]->c = $build->c;
    $placedAntennas++;    
}


/* SCORING & OUTPUT */
$output = $placedAntennas . PHP_EOL;
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
    }
}

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'score_' . $SCORE);
