<?php

use Utils\FileManager;
use Utils\Log;

$fileName = 'd';

$rCenter = 2800;
$cCenter = 1060;

include 'mm-reader.php';

/** @var Vehicle[] $VEHICLES */
/** @var Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehiclesCount */
/** @var int $ridesCount */
/** @var int $bonus */
/** @var int $steps */


// Algo

$freeVehicles = $VEHICLES;
/** @var Vehicle[] $busyVehicles */
$busyVehicles = [];
$ridesToTake = $RIDES;
/** @var Ride[] $ridesTaken */
$ridesTaken = [];

$TOTAL_SCORE = 0;

$t = 0;

while ($t < $steps) {
    Log::out("T = $t");

    // Libero i veicoli
    foreach ($busyVehicles as $v) {
        if ($v->freeAt <= $t) {
            $freeVehicles[$v->id] = $v;
            unset($busyVehicles[$v->id]);
        }
    }

    // Assegno ride ai veicoli liberi
    foreach ($freeVehicles as $v) {

        // Cerco la ride migliore per $v
        /** @var Ride $bestRide */
        $bestRide = null;
        $bestScore = null;
        $bestRealScore = null;
        $bestFreeAt = null;

        foreach ($ridesToTake as $r) {
            $distanceFromHere = $v->distanceFromRide($r);

            $lateness = $t + $r->distance + $distanceFromHere - $r->latestFinish;
            if ($lateness > 0) {
                continue;
            }
            $earliness = $r->earliestStart - $t - $distanceFromHere;
            $realScore = $r->distance + ($earliness >= 0 ? $bonus : 0);

            $score = 0;
            if ($earliness >= 0) {
                $score += $bonus - $earliness;
            }
            $distanceFromCenter = max(abs($r->rFinish - $rCenter) + abs($r->cFinish - $cCenter) - 1800, 0) + 10;
            $score += 10000 / pow($distanceFromCenter, 0.6);

            //$score = ($r->distance + $bonus) / $distanceFromHere;

            if ($bestScore === null || $score > $bestScore) {
                $bestRide = $r;
                $bestScore = $score;
                $bestRealScore = $realScore;
                $bestFreeAt = max($t + $distanceFromHere, $r->earliestStart) + $r->distance;
            }
        }

        // Assegno la ride migliore
        if ($bestRide !== null) {
            Log::out("Assegno ride {$bestRide->id} a veicolo {$v->id}. Si libererà a t = $bestFreeAt");
            $busyVehicles[$v->id] = $v;
            unset($freeVehicles[$v->id]);
            $ridesTaken[$bestRide->id] = $bestRide;
            unset($ridesToTake[$bestRide->id]);
            $v->currentR = $bestRide->rEnd;
            $v->currentC = $bestRide->cEnd;
            $v->freeAt = $bestFreeAt;
            $v->assignedRides[$bestRide->id] = $bestRide;
            $TOTAL_SCORE += $bestRealScore;
        } else {
            unset($freeVehicles[$v->id]);
        }

    }

    $t = min(array_map(function (Vehicle $v) {
        return $v->freeAt;
    }, $busyVehicles));
    if (!$t) break;
}

Log::out('Score: ' . $TOTAL_SCORE);

$output = '';
foreach ($VEHICLES as $v) {
    $output .= count($v->assignedRides) . ' ' . implode(' ', array_keys($v->assignedRides)) . "\n";
}
$fileManager = new FileManager($fileName);
$fileManager->output($output, $TOTAL_SCORE);
