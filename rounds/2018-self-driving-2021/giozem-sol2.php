<?php

use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'd';

include 'reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$result = [];
$points = 0;

foreach ($VEHICLES as $vehicleId => $vehicle) {
    Log::out('Inizio veicolo ' . $vehicle->id, 0);

    // Find vehicle path starting from the end (to get the longest ride available as last)
    $longestRide = null;
    foreach ($RIDES as $rideId => $ride) {
        if($longestRide == null || $longestRide->distance < $ride->distance) {
            $longestRide = $ride;
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

    // Log::out('Punti ride: ' . $longestRide->distance, 1);
    $points += $longestRide->distance;

    $RIDES->forget($longestRide->id);

    // Log::out('Faccio ride più lunga, r: ' . $vehicle->currentR . ' - c: ' . $vehicle->currentC . ' – rides: ' . count($result[$vehicle->id]), 1);

    while (true) {
        $bestRide = null;
        $bestRideScore = 0;
        $bestRidePoints = 0;
        foreach ($RIDES as $rideId => $ride) {
            if($ride == null) {
                // Log::out('OH OH, RIDE NULL!', 1);
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
                $bestRidePoints = $ride->distance + ($canGetBonus ? $bonus : 0);
            }
        }

        if($bestRide == null) {
            // Log::out('BEST RIDE NULL!', 1);
            break;
        }

        $result[$vehicle->id][] = $bestRide->id;

        $localSteps -= $vehicle->distanceFromRideEnd($bestRide);
        $localSteps -= $bestRide->distance;
        $vehicle->currentR = $bestRide->rStart;
        $vehicle->currentC = $bestRide->cStart;

        // Log::out('Punti ride: ' . $bestRidePoints, 1);
        $points += $bestRidePoints;

        // Log::out('Faccio ride migliore, r: ' . $vehicle->currentR . ' - c: ' . $vehicle->currentC . ' – rides: ' . count($result[$vehicle->id]), 1);

        $RIDES->forget($longestRide->id);
        // Log::out('Rimuovo ride migliore, rimanenti: ' . count($RIDES), 1);
    }

    // Reverse the result list for this vehicle, because we started from end
    $result[$vehicle->id] = array_reverse($result[$vehicle->id]);

    Log::out('Veicolo ' . $vehicle->id . ' finito', 0);
}

Log::out('FINITO! 🥳 punti totalizzati: ' . $points , 0);

$fileManager = new FileManager($fileName);
$output = [];
foreach ($result as $vehicleId => $rides) {
    $output[] = count($rides) . " " . implode(" ", $rides);
}
$fileManager->output(implode("\n", $output));
