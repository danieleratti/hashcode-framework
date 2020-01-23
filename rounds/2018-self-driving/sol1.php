<?php

use Utils\ArrayUtils;
use Utils\Stopwatch;

$fileName = 'd';

include 'reader.php';

$uselessCars = collect();

$timer = new Stopwatch('ciclone');
$timer2 = new Stopwatch('ciclino');

while (count($cars) > 0 && count($rides) > 0) {
    $scores = [];

    $timer->tik();
    /** @var Car $car */
    foreach ($cars as $car) {
        $max = 0;

        foreach ($rides as $ride) {
            $score = $car->getRidePoints($ride);
            if ($score == 0)
                continue;

            if ($max < $score)
                $max = $score;

            $scores[] = [
                'score' => $score,
                'carId' => $car->id,
                'rideId' => $ride->id,
            ];
        }

        if ($max == 0) {
            $uselessCars->add($car);
            $cars->forget($car->id);
        }
    }

    ArrayUtils::array_keysort($scores, 'score', SORT_DESC);

    while (count($scores) > 0) {
        $score = array_shift($scores);

        $cars->get($score['carId'])->takeRide($rides->get($score['rideId']));
        $scores = array_filter($scores, function ($r) use ($score) {
            return $r['carId'] != $score['carId'] && $r['rideId'] != $score['rideId'];
        });
    }

    $timer->tok();
}

$output = [];
foreach ($cars as $car) {
    $output[] = $car->toString();
}
foreach ($uselessCars as $car) {
    $output[] = $car->toString();
}

$fileManager->output(implode("\n", $output));
