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
    foreach ($car->streets as $street) {
        if (!isset($street->end->streetToCongestion[$street->name])) {
            $street->end->streetToCongestion[$street->name] = 0;
        }

        $street->end->streetToCongestion[$street->name]++;
    }
}

foreach ($INTERSECTIONS as $i) {
    $tot = array_reduce($i->streetToCongestion, function ($carry, $perc) {
        return $carry + $perc;
    }, 0);

    foreach ($i->streetToCongestion as $s => $time) {
        $i->streetToCongestion[$s] = ceil($time / $tot * 10 * $param1);
    }

    $i->streetToCongestion = array_filter($i->streetToCongestion, function ($s) {
        return $s > 0;
    });
}


/* OUTPUT */
Log::out('Output...');
$INTERSECTIONS = array_filter($INTERSECTIONS, function ($i) {
    return count($i->streetToCongestion) > 0;
});
$output = count($INTERSECTIONS) . PHP_EOL;
foreach ($INTERSECTIONS as $intersection) {
    $output .= $intersection->id . PHP_EOL;
    $output .= count($intersection->streetToCongestion) . PHP_EOL;
    foreach ($intersection->streetToCongestion as $semaphoreId => $time) {
        $output .= $semaphoreId . " " . $time . PHP_EOL;
    }
}
$fileManager->outputV2($output, 'score_' . $SCORE);
Autoupload::submission($fileName, null, $output);
