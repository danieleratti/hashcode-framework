<?php

use Utils\ArrayUtils;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'e';

include 'reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var FileManager $fileManager */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

// config
define('bigPixelSize', 100); // TUNE THIS
define('bigPixelNeighbors', 2); // TUNE THIS

// vars
$SCORE = 0;
$OUTPUT = [];
$bigPixel2rides = []; // [bigPixelR][bigPixelC] = [ride1ID => ride1, ride2ID => ride2, ...]

// functions
function getScore($vehicle, $ride)
{
    /** @var Vehicle $vehicle */
    /** @var Ride $ride */

    global $steps, $bonus;

    $score = [
        'score' => 0,
        'bonus' => 0,
        'myscore' => 0,
        'freeAt' => 0,
    ];

    $startingDistance = $vehicle->distanceFromStartingRide($ride);
    $finishingDistance = $vehicle->distanceFromFinishingRide($ride);
    $takeAt = $vehicle->freeAt + $startingDistance;
    $freeAt = $vehicle->freeAt + $finishingDistance;
    if ($freeAt > $steps || $freeAt > $ride->latestFinish)
        return $score;

    $score['freeAt'] = $freeAt;

    if ($takeAt <= $ride->earliestStart)
        $score['bonus'] = 1;

    $bonusTaken = $score['bonus'] * $bonus;

    $score['score'] = $ride->distance + $bonusTaken;

    //$score['myscore'] = ($ride->distance + pow($bonusTaken, 2.0)) / ($startingDistance); // version B @ 176.877 points

    $urgencyBonus = 0;
    if($bonusTaken > 0) {
        if($ride->earliestStart - $vehicle->freeAt < 50000)
            $urgencyBonus += $bonusTaken*20;
    }
    $score['myscore'] = ($score['score'] + $urgencyBonus) / ($startingDistance); // TUNE THIS

    return $score;
}

function takeRide($vehicle, $ride)
{
    global $SCORE, $OUTPUT, $RIDES, $bigPixel2rides;

    /** @var Vehicle $vehicle */
    /** @var Ride $ride */
    $score = getScore($vehicle, $ride);
    if ($score['score'] == 0)
        Log::error("Stai provando a prendere una ride che vale 0 punti!");

    $OUTPUT[$vehicle->id][] = $ride->id;
    $SCORE += $score['score'];
    $vehicle->freeAt = $score['freeAt'];
    $vehicle->currentR = $ride->rFinish;
    $vehicle->currentC = $ride->cFinish;

    Log::out("Vehicle {$vehicle->id} (now @ R={$vehicle->currentR} C={$vehicle->currentC} T={$vehicle->freeAt}) took ride {$ride->id}. SCORE = $SCORE");

    $bigPixel = getBigPixel($ride->rStart, $ride->cStart);
    unset($bigPixel2rides[$bigPixel[0]][$bigPixel[1]][$ride->id]);

    $RIDES->forget($ride->id);
}

function getBigPixel($r, $c)
{
    return [floor($r / bigPixelSize), floor($c / bigPixelSize)];
}

function getNeighborRides($r, $c, $bigPixelNeighbors = false)
{
    global $bigPixel2rides, $RIDES;

    if ($bigPixelNeighbors == -1)
        return $RIDES;

    if (!$bigPixelNeighbors)
        $bigPixelNeighbors = bigPixelNeighbors;
    $bigPixel = getBigPixel($r, $c);
    $r = $bigPixel[0];
    $c = $bigPixel[1];

    $rides = [];
    for ($_r = $r - $bigPixelNeighbors; $_r <= $r + $bigPixelNeighbors; $_r++) {
        for ($_c = $c - $bigPixelNeighbors; $_c <= $c + $bigPixelNeighbors; $_c++) {
            if ($bigPixel2rides[$_r][$_c])
                $rides = array_merge($rides, $bigPixel2rides[$_r][$_c]);
        }
    }

    return $rides;
}

function getScoredNeighborRides(Vehicle $vehicle, $r, $c, $bigPixelNeighbors = false)
{
    $_rides = getNeighborRides($r, $c, $bigPixelNeighbors);
    $rides = [];
    foreach ($_rides as $ride) {
        /** @var Ride $ride */
        $score = getScore($vehicle, $ride);
        $rides[] = ['myscore' => $score['myscore'], 'score' => $score['score'], 'ride' => $ride];
    }
    ArrayUtils::array_keysort($rides, 'myscore', 'DESC');
    return $rides;
}

function getBestScoredNeighborRide(Vehicle $vehicle, $r, $c, $bigPixelNeighbors = false)
{
    $scoredRides = getScoredNeighborRides($vehicle, $r, $c, $bigPixelNeighbors);
    foreach ($scoredRides as $ride)
        if ($ride['score'] > 0)
            return $ride;
        else
            return false;
    return false;
}

//takeRide($VEHICLES[0], $RIDES[0]);

// *** ALGO ***

/*
// ciclo tutti i veicoli (si parte dai veicoli!!!)
    // ciclo while sul given vehicle finché non si esce, e finché freeAt < $steps
        // seleziono tutte le ride con start vicine (TUNE THIS indirectly dalle define config)
        // calcolo gli score (in modo intelligente per alleggerire)
        // se non esistono ride fattibili, breakko
        // eseguo la ride
*/

Log::out("Heating bigPixels...");
foreach ($RIDES as $ride) {
    $bigPixel = getBigPixel($ride->rStart, $ride->cStart);
    $bigPixel2rides[$bigPixel[0]][$bigPixel[1]][$ride->id] = $ride;
}

Log::out("Algo...");
while (count($VEHICLES) > 0) {
    /** @var Vehicle $vehicle */
    $vehicle = $VEHICLES->sortBy('freeAt')->first();
    Log::out("Running vehicle {$vehicle->id} with T={$vehicle->freeAt}/$steps");
    $bestNearRide = getBestScoredNeighborRide($vehicle, $vehicle->currentR, $vehicle->currentC);
    if (!$bestNearRide)
        $bestNearRide = getBestScoredNeighborRide($vehicle, $vehicle->currentR, $vehicle->currentC, 10); // TUNE THIS
    if (!$bestNearRide)
        $bestNearRide = getBestScoredNeighborRide($vehicle, $vehicle->currentR, $vehicle->currentC, -1);
    if (!$bestNearRide) {
        $VEHICLES->forget($vehicle->id);
        continue;
    }
    takeRide($vehicle, $bestNearRide['ride']);
}

Log::out("FINAL SCORE = $SCORE");

$output = [];
foreach ($OUTPUT as $o) {
    $output[] = count($o) . " " . implode(" ", $o);
}
$output = implode("\n", $output);
$fileManager->output($output, $SCORE);
