<?php

include 'reader.php';
include 'readOutput.php';

use Utils\FileManager;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

// Reading the inputs
$fileManager = new FileManager($fileName);
new Initializer($fileManager);

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
        $freeRides = Initializer::$RIDES->filter(function ($item) {
            return !$item->alreadyTaken();
        });

        if ($freeRides->count()) {
            $car->takeRide($freeRides->first(), $currentTime);
        } else {
            break;
        }
    }
}

$content = '';
foreach (Initializer::$CARS as $car) {
    $row = count($car->rides);
    foreach ($car->rides as $ride) {
        $row .= ' ' . $ride->id;
    }

    $content .= $row . "\n";
}

$fileManager->output($content);

$readerOutput = new ReaderOutput($fileManager);
$readerOutput->getResult();
