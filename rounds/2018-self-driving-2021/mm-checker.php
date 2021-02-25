<?php

use Utils\FileManager;
use Utils\Log;

$fileName = 'e';
$outputName = 'mm-sol1_e_high_bonus_21430431.txt';

include 'mm-reader.php';

/** @var Vehicle[] $VEHICLES */
/** @var Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehiclesCount */
/** @var int $ridesCount */
/** @var int $bonus */
/** @var int $steps */

$content = explode("\n", file_get_contents(__DIR__ . '/output/' . $outputName));

$ridesTaken = [];
$totalScore = 0;

foreach ($content as $vehicleId => $c) {
    /** @var Vehicle $ride */
    $vehicle = $VEHICLES[$vehicleId];
    $ridesIds = explode(" ", $c);
    array_shift($ridesIds);

    $t = 0;
    foreach ($ridesIds as $rideId) {
        if($rideId >= $ridesCount) {
            Log::error("La ride $rideId non esiste!");
        }
        if(isset($ridesTaken[$rideId])) {
            Log::error("La ride $rideId è già stata presa!");
        }

        /** @var Ride $ride */
        $ride = $RIDES[$rideId];
        $score = 0;

        $distanceFromHere = $vehicle->distanceFromRide($ride);
        $minStartAt = $t + $distanceFromHere;
        $startAt = max($minStartAt, $ride->earliestStart);
        $freeAt = $startAt + $ride->distance;

        if($freeAt > $steps) {
            Log::error("La ride $rideId va oltre il tempo massimo!");
        }
        if($freeAt > $ride->latestFinish) {
            Log::out("La ride $rideId ha ritardato troppo! (freeAt = $freeAt > {$ride->latestFinish})");
            $score = 0;
        } else {
            $score = $ride->distance;
            if($startAt == $ride->earliestStart){
                $score += $bonus;
            }
        }

        $totalScore += $score;
        $t = $freeAt;
    }
}

Log::out("Score: $totalScore");
