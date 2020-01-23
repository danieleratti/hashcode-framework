<?php

$fileName = 'e';

include 'reader.php';

$freeCarsAtTime[0] = $cars->toArray();

$points = 0;
for ($t = 0; $t < $T; $t++) {
    $freeCars = $freeCarsAtTime[$t];

    $count = $freeCars ? count($freeCars) : 0;
    $perc = round($t / $T * 100);
    echo "tempo: $perc -> $count\n";

    if (!$count)
        continue;

    /** @var Car $car */
    foreach ($freeCars as $car) {
        $bestScore = 0;
        $bestRide = null;

        /** @var Ride $ride */
        foreach ($rides as $ride) {
            $score = $car->getRidePoints($ride);

            if ($bestScore < $score) {
                $bestScore = $score;
                $bestRide = $ride;
            }
        }

        if ($bestRide) {
            $points += $car->takeRide($bestRide);
            $freeCarsAtTime[$car->freeAt][] = $car;
        }
    }
}

$output = [];
foreach ($cars as $car) {
    $output[] = $car->toString();
}

$fileManager->output(implode("\n", $output));
echo "\n\nPUNTEGGIO: $points";
