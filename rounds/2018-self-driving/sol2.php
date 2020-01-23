<?php

use Utils\Stopwatch;

$fileName = 'd';

include 'reader.php';

$uselessCars = collect();

$timer = new Stopwatch('ciclone');
$timer2 = new Stopwatch('ciclino');

$n = 0;
while (count($cars) > 0 && count($rides) > 0) {
    $bestCar = null;
    $bestRide = null;
    $bestScore = 0;

    /** @var Car $car */
    foreach ($cars as $car) {
        $bestCarScore = 0;
        foreach ($rides as $ride) {
            $score = $car->getRidePoints($ride);

            if ($score == 0)
                continue;

            if ($bestScore < $score) {
                $bestScore = $score;
                $bestCar = $car;
                $bestRide = $ride;
            }
            if ($bestCarScore < $score)
                $bestCarScore = $score;
        }

        if ($bestCarScore == 0) {
            $uselessCars->add($car);
            $cars->forget($car->id);
        }
    }

    if (!$bestCar)
        break;

    echo "ciclo " . $n++ . "\n";
    $bestCar->takeRide($bestRide);
}

$output = [];
foreach ($cars as $car) {
    $output[] = $car->toString();
}
foreach ($uselessCars as $car) {
    $output[] = $car->toString();
}

$fileManager->output(implode("\n", $output));
