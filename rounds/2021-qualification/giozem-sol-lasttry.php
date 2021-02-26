<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = null;
$param1 = null;
Cerberus::runClient(['fileName' => 'b', 'param1' => 1.0]);
Autoupload::init();

include 'giozem-reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Street[] $STREETS */
/** @var Collection|Intersection[] $INTERSECTIONS */
/** @var Collection|Car[] $CARS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

$SCORE = 0;


/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = $param1;

// Filtro le car che non hanno streets
$CARS = array_filter($CARS, function ($c) {
    return count($c->streets) > 0;
});

foreach ($CARS as $car) {
    if (!isset($car->startingStreet->end->streetToScore[$car->startingStreet->name])) {
        $car->startingStreet->end->streetToScore[$car->startingStreet->name] = 0;
    }
    $car->startingStreet->end->streetToScore[$car->startingStreet->name]++;

    foreach ($car->streets as $k => $street) {
        if ($k == count($car->streets) - 1) {
            continue;
        }
        if (!isset($street->end->semaphoreToTime[$street->name])) {
            $street->end->semaphoreToTime[$street->name] = 0;
        }

        $street->end->semaphoreToTime[$street->name] += 1 / $car->pathDuration;
    }
}

foreach ($INTERSECTIONS as $i) {
    $tot = array_reduce($i->semaphoreToTime, function ($carry, $perc) {
        return $carry + $perc;
    }, 0);

    foreach ($i->semaphoreToTime as $s => $time) {
        $i->semaphoreToTime[$s] = ceil($time / $tot * $param1);
    }
    $i->semaphoreToTime = array_filter($i->semaphoreToTime, function ($s) {
        return $s > 0;
    });

    arsort($i->streetToScore);
    foreach ($i->streetToScore as $street => $carCount) {
        $i->streetToScore[$street] = $i->semaphoreToTime[$street];
    }
}


/* OUTPUT */
Log::out('Output...');
$INTERSECTIONS = array_filter($INTERSECTIONS, function ($i) {
    return count($i->streetToScore) > 0;
});
$output = count($INTERSECTIONS) . PHP_EOL;
foreach ($INTERSECTIONS as $intersection) {
    $output .= $intersection->id . PHP_EOL;
    $output .= count($intersection->streetToScore) . PHP_EOL;
    foreach ($intersection->streetToScore as $semaphoreId => $time) {
        $output .= $semaphoreId . " " . $time . PHP_EOL;
    }
}
$fileManager->outputV2($output, 'score_' . $SCORE);
Autoupload::submission($fileName, null, $output);
