<?php

include 'reader.php';
include 'readOutput.php';

use Utils\FileManager;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'c';

// Reading the inputs
$fileManager = new FileManager($fileName);
new Initializer($fileManager);

// $visual = new VisualStandard($R, $C);2348 = {Ride} [12]
//
// /** @var Ride $ride */
// foreach ($rides as $ride) {
//     $visual->setPixel($ride->rStart, $ride->cStart, Colors::green4);
//     $visual->setPixel($ride->rEnd, $ride->cEnd, Colors::red4);
// }
//
// $visual->save($fileName);

Initializer::$RIDES->sortBy('earlyStart');

for ($currentTime = 0; $currentTime <= Initializer::$TIME; $currentTime++) {
    /** @var Car $car */
    foreach (Initializer::$RIDES as $ride) {
        $car = getBestCar($ride, $currentTime);
        if(!$car) continue;
        $car->takeRide($ride, $currentTime);
    }
}


function getBestCar(Ride $ride, $currentTime)
{
    $bestCar = null;
    /** @var Car $car */
    foreach(Initializer::$CARS as $car) {
        if($car->freeAt > $currentTime) continue;

        if(!$bestCar) {
            $bestCar = $car;
            continue;
        }

        $currentCar = $car->getWastedTime($ride);
        if($bestCar->getWastedTime($ride) > $currentCar) {
            $bestCar = $car;
        }
    }

    return $bestCar;
}

$content = '';
$total = 0;
foreach (Initializer::$CARS as $car) {
    $row = count($car->rides);
    foreach ($car->rides as $ride) {
        $row .= ' ' . $ride->id;
        /** @var Ride $ride */
        $total += $ride->points;
    }

    $content .= $row . "\n";
}

echo $total . PHP_EOL;

$fileManager->output($content);


// a => 10
// b => 121412
// c => ???
// d => ???