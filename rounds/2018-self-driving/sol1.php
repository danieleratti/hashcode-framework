<?php

include 'reader.php';

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';
$inizialier = new Initializer($fileName);

// $visual = new VisualStandard($R, $C);
//
// /** @var Ride $ride */
// foreach ($rides as $ride) {
//     $visual->setPixel($ride->rStart, $ride->cStart, Colors::green4);
//     $visual->setPixel($ride->rEnd, $ride->cEnd, Colors::red4);
// }
//
// $visual->save($fileName);

for ($currentTime = 0; $currentTime <= Initializer::$TIME; $currentTime++) {
    /** @var Car $car */
    foreach (Initializer::$CARS as $car) {
        if($inizialier::$RIDES->count())
            $car->takeRide($inizialier::$RIDES->first(), $currentTime);
        else break;
    }
}
