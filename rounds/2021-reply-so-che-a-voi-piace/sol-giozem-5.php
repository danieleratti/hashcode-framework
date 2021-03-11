<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'b';
$MIN_B = 50;
$MAX_FR = 3;
Cerberus::runClient(['fileName' => 'b', 'MIN_B' => 50, 'MAX_FR' => 3]);
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
$unavailableCoords = [];

/* FUNCTIONS */

function randomCoords() {
    global $W, $H;
    return [rand(0, $H - 1), rand(0, $W - 1)];
}

/* ALGO */

// TODO
$mapAntennas = [];
$mapBuildings = [];
foreach ($buildings as $building) {
    $mapBuildings[$building->r . "_" .$building->c] = $building;
}

$placedCount = 0;

foreach ($antennas as $antenna) {
    Log::out("Placing antenna $antenna->id");
    $placed = false;
    $failedRuns = 0;
    $minBuildings = $MIN_B;
    $maxFailedRuns = $MAX_FR;
    $maxRetry = 5;
    $retries = 0;

    while(!$placed) {
        $coords = randomCoords();

        if($mapAntennas[$coords[0] . "_" . $coords[1]]) {
            Log::out("Antenna already present");
            continue;
        }

        $range = $antenna->range;
        $start = [$coords[0] - $range, $coords[1] - $range];
        $end = [$coords[0] + $range, $coords[1] + $range];

        $countBuildings = 0;
        for ($i = $start[0]; $i < $end[0]; $i++) {
            for ($j = $start[1]; $j < $end[1]; $j++) {
                if($mapBuildings[$i . "_" . $j]) {
                    $countBuildings++;
                }
            }
        }

        if($countBuildings >= $minBuildings) {
            // place
            $antenna->r = $coords[0];
            $antenna->c = $coords[1];
            $mapAntennas[$coords[0] . "_" . $coords[1]] = $antenna;
            $placedCount++;
            $placed = true;
        } else {
            $failedRuns++;
            if($failedRuns >= $maxFailedRuns) {
                $minBuildings--;
                if($minBuildings == 0 && $retries <= $maxRetry) {
                    $minBuildings = (int) floor($MIN_B / 2);
                    $retries++;
                }
            }
        }
    }
    $remaining = count($antennas) - $placedCount;
    Log::out("Placed, remaining $remaining");
}

/* SCORING & OUTPUT */
$numPlacedAntennas = 0;
$output = "";
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $numPlacedAntennas++;
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
    } else {
        Log::out("Antenna not placed");
    }
}
$output = $numPlacedAntennas . PHP_EOL . $output;

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'minb' . $MIN_B . 'maxfr' . $MAX_FR);
