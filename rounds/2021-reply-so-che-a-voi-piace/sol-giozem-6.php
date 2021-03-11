<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'b';
$MIN_B = 100;
$MAX_FR = 3;
$MAX_R = 10;
Cerberus::runClient(['fileName' => 'b', 'MIN_B' => 100, 'MAX_FR' => 3, 'MAX_R' => 10]);
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

function randomCoords()
{
    global $W, $H;
    return [rand(0, $H - 1), rand(0, $W - 1)];
}

/* ALGO */

function compareAntenna($a, $b)
{
    // return $a->speed > $b->speed;
    return ($a->speed / $a->range) > ($b->speed / $b->range);
    // return ($a->speed * $a->range) > ($b->speed * $b->range);
}

usort($antennas, "compareAntenna");

$mapAntennas = [];
$mapBuildings = [];
foreach ($buildings as $building) {
    $mapBuildings[$building->r . "_" . $building->c] = $building;
}

$placedCount = 0;

foreach ($antennas as $antenna) {
    Log::out("Placing antenna $antenna->id");
    $placed = false;
    $failedRuns = 0;
    $minBuildings = $MIN_B;
    $maxFailedRuns = $MAX_FR;
    $maxRuns = $MAX_R;

    $maxCountBuildings = 0;
    $maxCoords = null;
    $countRuns = 0;
    while ($countRuns < $maxRuns) {
        $coords = randomCoords();

        if ($mapAntennas[$coords[0] . "_" . $coords[1]]) {
            Log::out("Antenna already present");
            continue;
        }

        $range = $antenna->range;
        $start = [$coords[0] - $range, $coords[1] - $range];
        $end = [$coords[0] + $range, $coords[1] + $range];

        $countBuildings = 0;
        for ($i = $start[0]; $i < $end[0]; $i++) {
            for ($j = $start[1]; $j < $end[1]; $j++) {
                if ($mapBuildings[$i . "_" . $j]) {
                    $countBuildings++;
                }
            }
        }

        if ($countBuildings >= $minBuildings) {
            if ($maxCoords == null || $countBuildings > $maxCountBuildings) {
                $maxCoords = $coords;
                $maxCountBuildings = $countBuildings;
            }
            $countRuns++;
        } else {
            $failedRuns++;
            if ($failedRuns >= $maxFailedRuns) {
                $minBuildings--;
            }
        }
    }

    // place
    $antenna->r = $maxCoords[0];
    $antenna->c = $maxCoords[1];
    $mapAntennas[$maxCoords[0] . "_" . $maxCoords[1]] = $antenna;
    $placedCount++;

    $remaining = count($antennas) - $placedCount;
    Log::out("Placed, remaining $remaining");
}

/* SCORING & OUTPUT */
$numPlacedAntennas = 0;
$output = "";
foreach ($antennas as $antenna) {
    if ($antenna->placed()) {
        $numPlacedAntennas++;
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
    } else {
        Log::out("Antenna not placed");
    }
}
$output = $numPlacedAntennas . PHP_EOL . $output;

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'minb' . $MIN_B . 'maxfr' . $MAX_FR . 'maxr' . $MAX_R);
