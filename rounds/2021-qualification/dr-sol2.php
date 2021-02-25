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
Cerberus::runClient(['fileName' => 'b' /*, 'param1' => 1.0*/]);
Autoupload::init();

include 'dr-reader-2.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Car[] $CARS */
/** @var Collection|Street[] $STREETS */
/** @var Collection|Intersection[] $INTERSECTIONS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

$SCORE = 0;

/* COLLECTIONS */
$CARS = collect($CARS);
$CARS->keyBy('id');

$STREETS = collect($STREETS);
$STREETS->keyBy('name');

$INTERSECTIONS = collect($INTERSECTIONS);
$INTERSECTIONS->keyBy('id');


/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = 0;

$CARS = $CARS->where('nStreets', '>', 0);

$OUTPUT = [];

foreach($CARS as $car) {
    $car->calcPriority();
}

foreach($INTERSECTIONS as $intersection) {
    $streetsInDuration = [];
    $totalPriorities = 0;
    $streetsInPriorities = [];
    foreach($intersection->streetsIn as $streetIn) {
        $priority = $streetIn->priority;
        $streetsInPriorities[$streetIn->name] = $priority;
        $totalPriorities += $priority;
    }
    $cycleDuration = min($DURATION, 10);
    foreach($streetsInPriorities as $name => $priority) {
        if($priority > 0) {
            $streetsInDuration[$name] = ceil($priority / $totalPriorities * $cycleDuration);
        }
    }
    if(count($streetsInDuration) > 0) {
        $OUTPUT[$intersection->id] = $streetsInDuration;
    }
}

/* OUTPUT */
Log::out('Output...');
$output = [];
$output[] = count($OUTPUT);
foreach($OUTPUT as $id => $o) {
    $output[] = $id;
    $output[] = count($o);
    foreach($o as $k => $v) {
        $v = (int)$v;
        $output[] = "$k $v";
    }
}
$output = implode("\n", $output);
$fileManager->outputV2($output, 'time_' . time());
Autoupload::submission($fileName, null, $output);
