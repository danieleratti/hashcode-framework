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
$param2 = null;
Cerberus::runClient(['fileName' => 'c', 'param1' => 1.0]);
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
    foreach ($car->streets as $street) {
        if (!isset($street->end->semaphoreToTime[$street->name])) {
            $street->end->semaphoreToTime[$street->name] = 0;
        }
        $street->end->semaphoreToTime[$street->name] += $car->pathDuration;

        if (!isset($street->end->streetToCongestion[$street->name])) {
            $street->end->streetToCongestion[$street->name] = 0;
        }
        $street->end->streetToCongestion[$street->name]++;
    }
}

foreach ($INTERSECTIONS as $intersection) {
    foreach ($intersection->semaphoreToTime as $street => $time) {
        $numCars = $intersection->streetToCongestion[$street];
        $intersection->streetToScore[$street] = pow($numCars, $param1) / $time;
    }
}

foreach ($INTERSECTIONS as $i) {
    $tot = array_reduce($i->streetToScore, function ($carry, $perc) {
        return $carry + $perc;
    }, 0);

    foreach ($i->streetToScore as $s => $time) {
        $i->streetToScore[$s] = ceil($time / $tot * 10);
    }

    $i->streetToScore = array_filter($i->streetToScore, function ($s) {
        return $s > 0;
    });
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
