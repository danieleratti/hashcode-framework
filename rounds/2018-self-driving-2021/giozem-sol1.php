<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'd';

include 'mm-reader.php';

/** @var Vehicle[] $VEHICLES */
/** @var Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehiclesCount */
/** @var int $ridesCount */
/** @var int $bonus */
/** @var int $steps */

$result = [];
$points = 0;

for ($x = 0; $x < count($VEHICLES); $x++) {
    $vehicle = $VEHICLES[$x];

    Log::out('Inizio veicolo ' . $vehicle->id, 0);

    // Find vehicle path starting from the end (to get the longest ride available as last)
    $longestRide = null;
    $longestRideIndex = -1;
    for ($i = 0; $i < count($RIDES); $i++) {
        $ride = $RIDES[$i];
        if($longestRide == null || $longestRide->distance < $ride->distance) {
            $longestRide = $ride;
            $longestRideIndex = $i;
        }
    }

    if($longestRide == null) {
        Log::out('OH OH, LONGEST RIDE NULL! Rides rimanenti: ' . count($RIDES), 1);
        break;
    }

    $result[$vehicle->id] = [];
    $result[$vehicle->id][] = $longestRide->id;

    $localSteps = $steps - $longestRide->distance;
    $vehicle->currentR = $longestRide->rStart;
    $vehicle->currentC = $longestRide->cStart;

    unset($RIDES[$longestRideIndex]);

    // Log::out('Faccio ride più lunga, r: ' . $vehicle->currentR . ' - c: ' . $vehicle->currentC . ' – rides: ' . count($result[$vehicle->id]), 1);

    while (true) {
        $bestRide = null;
        $bestRideScore = 0;
        $bestRideIndex = -1;
        $bestRidePoints = 0;
        for ($i = 0; $i < count($RIDES); $i++) {
            $ride = $RIDES[$i];

            if($ride == null) {
                continue;
            }

            $distForRide = $vehicle->distanceFromRideEnd($ride);
            $rideDistFromZero = abs(0 - $ride->rStart) + abs(0 - $ride->cStart);
            $totalDistToRun = $distForRide + $rideDistFromZero + $ride->distance;

            $canBeDone = $totalDistToRun <= $localSteps;

            if(!$canBeDone) {
                continue;
            }

            $canGetBonus = $ride->latestFinish < $localSteps && $ride->earliestStart >= ($localSteps - $distForRide - $ride->distance);
            $score = ($ride->distance / $distForRide) * ($canGetBonus ? $bonus : 1);

            if($bestRide == null || $bestRideScore < $score) {
                $bestRide = $ride;
                $bestRideScore = $score;
                $bestRideIndex = $i;
                $bestRidePoints = $ride->distance + ($canGetBonus ? $bonus : 0);
            }
        }

        if($bestRide == null) {
            break;
        }

        $result[$vehicle->id][] = $bestRide->id;

        $localSteps -= $vehicle->distanceFromRideEnd($bestRide);
        $localSteps -= $bestRide->distance;
        $vehicle->currentR = $bestRide->rStart;
        $vehicle->currentC = $bestRide->cStart;

        $points += $bestRidePoints;

        // Log::out('Faccio ride migliore, r: ' . $vehicle->currentR . ' - c: ' . $vehicle->currentC . ' – rides: ' . count($result[$vehicle->id]), 1);

        unset($RIDES[$bestRideIndex]);
        // Log::out('Rimuovo ride migliore, rimanenti: ' . count($RIDES), 1);
    }

    // Reverse the result list for this vehicle, because we started from end
    $result[$vehicle->id] = array_reverse($result[$vehicle->id]);

    Log::out('Veicolo ' . $vehicle->id . ' finito,  rimangono: ' . (count($VEHICLES) - $x) , 0);
}

Log::out('FINITO! 🥳 punti totalizzati: ' . $points , 0);
