<?php

use Utils\ArrayUtils;
use Utils\Stopwatch;

$fileName = 'e';

$kThresholdCombinationMaxScore = 0.95;
$kChunkCarRidesEachTime = 50;

include 'reader.php';

$SCORE = 0;

$uselessCars = collect();

$timer = new Stopwatch('ciclone');
$timer2 = new Stopwatch('ciclino');

$combinationMaxScore = 0;

//heating dell combinationMaxScore
foreach ($rides as $ride) {
    $score = $cars->get(0)->getRidePoints($ride);
    if ($score > $combinationMaxScore)
        $combinationMaxScore = $score;
}

echo "Heated\n";

while (count($cars) > 0 && count($rides) > 0) {
    echo "combinationMaxScore = $combinationMaxScore\n";
    $_combinationMaxScore = 0;
    $scores = [];

    $timer->tik();
    /** @var Car $car */
    foreach ($cars as $car) {
        $max = 0;
        foreach ($rides as $ride) {
            $score = $car->getRidePoints($ride);

            if ($max < $score)
                $max = $score;

            if ($score < $combinationMaxScore * $kThresholdCombinationMaxScore)
                continue;

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

        if ($max > $_combinationMaxScore)
            $_combinationMaxScore = $max;
    }

    echo "Scores count = " . count($scores) . "\n";
    ArrayUtils::array_keysort($scores, 'score', SORT_DESC);

    $chunk = $kChunkCarRidesEachTime;
    while (count($scores) > 0 && $chunk > 0) {
        $score = array_shift($scores);

        $SCORE += $cars->get($score['carId'])->takeRide($rides->get($score['rideId']));
        $scores = array_filter($scores, function ($r) use ($score) {
            return $r['carId'] != $score['carId'] && $r['rideId'] != $score['rideId'];
        });
        $chunk--;
    }

    $combinationMaxScore = $_combinationMaxScore;
    $timer->tok();
    echo "Remaining cars = " . count($cars) . " & Remaining rides = " . count($rides)."\n";
}

$output = [];
foreach ($cars as $car) {
    $output[] = $car->toString();
}
foreach ($uselessCars as $car) {
    $output[] = $car->toString();
}

$fileManager->output(implode("\n", $output));

echo "SCORE: $SCORE";
