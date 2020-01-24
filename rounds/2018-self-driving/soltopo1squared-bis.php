<?php

/* schifo */

use Utils\ArrayUtils;
use Utils\Stopwatch;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'd';

$kThresholdCombinationMaxScore = 0.9;
$kChunkCarRidesEachTime = 10;

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

$padding = -500000;
$corner = [[ //C,R
    0+$padding, 1580+$padding
], [
    3960-$padding, 2280-$padding
]];

$rides = $rides->filter(function (Ride $ride) use ($corner) {
    return (
        $ride->rStart >= $corner[0][1] && $ride->rEnd >= $corner[0][1] &&
        $ride->rStart <= $corner[1][0] && $ride->rEnd <= $corner[1][0] &&
        $ride->cStart >= $corner[0][0] && $ride->cEnd >= $corner[0][0] &&
        $ride->cStart <= $corner[1][1] && $ride->cEnd <= $corner[1][1]);
});

$visual = new VisualStandard($R, $C);

/** @var Ride $ride */
foreach ($rides as $ride) {
    $rMed = ($ride->rStart + $ride->rEnd) / 2;
    $cMed = ($ride->cStart + $ride->cEnd) / 2;

    $visual->setLine($ride->rStart, $ride->cStart, $rMed, $cMed, Colors::green5);
    $visual->setLine($rMed, $cMed, $ride->rEnd, $ride->cEnd, Colors::red5);
}

$visual->save('line_' . $fileName);

die();

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
            $score = $car->getRidePoints($ride); //TODO: Aggiustare in base a BONUS

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
    echo "Remaining cars = " . count($cars) . " & Remaining rides = " . count($rides) . "\n";
    echo "\nSCORE: $SCORE";
}

$output = [];
foreach ($cars as $car) {
    $output[] = $car->toString();
}
foreach ($uselessCars as $car) {
    $output[] = $car->toString();
}

$fileManager->output(implode("\n", $output));

echo "\nFINAL SCORE: $SCORE";
