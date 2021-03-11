<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'f';
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
    $minBuildings = 50;
    $failedRuns = 0;
    $maxFailedRuns = 3;

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
                /*
                if($minBuildings == 0) {
                    Log::out("Min buildings = 0");
                    break;
                }
                */
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
$fileManager->outputV2($output, 'score_' . $SCORE);
