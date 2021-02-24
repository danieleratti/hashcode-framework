<?php

$fileName = 'd';
$outputName = 'giozem-sol2_d_metropolis.txt';

include 'reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$content = trim(file_get_contents(__DIR__ . '/output/' . $outputName));
$rows = explode("\n", $content);

if (count($rows) > $vehicles) {
    die('troppi veicoli');
}

$usedRides = [];

$points = 0;
foreach ($rows as $row) {
    $outRides = explode(' ', $row);
    array_shift($outRides);

    $vehicle = new Vehicle();
    $localSteps = 0;
    foreach ($outRides as $rideId) {
        if (in_array($rideId, $usedRides)) {
            die('ride usata due volte ' . $rideId);
        }

        $ride = $RIDES->get($rideId);

        $localSteps += $vehicle->distanceFromRide($ride);

        $canGetBonus = $ride->latestFinish > $localSteps && $ride->earliestStart <= $localSteps;
        $points += $ride->distance + ($canGetBonus ? $bonus : 0);
        $vehicle->currentR = $ride->rFinish;
        $vehicle->currentC = $ride->cFinish;

        $localSteps += $ride->distance;

        if($localSteps > $steps) {
            die('steps massimi superati ' . $localSteps);
        }

        $usedRides[] = $rideId;
    }
}

echo "BRAVO! punteggio $points";
