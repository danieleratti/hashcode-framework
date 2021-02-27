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
Cerberus::runClient(['fileName' => 'f' /*, 'param1' => 1.0*/]);
Autoupload::init();

include 'mm-reader.php';

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

/* ALGO */
Log::out("Run with fileName $fileName");
die();
// Execution

$lastRun = false;
$cycles = 10;

$semaphoreTimes = [];

for ($o = 0; $o < $cycles; $o++) {

    if ($o == $cycles - 1) $lastRun = true;


    $SCORE = 0;

    $fixedIntersections = [];
    $variableIntersections = $INTERSECTIONS;

    // Init

    foreach ($STREETS as $streetName => $street) {
        if ($street->usage === 0) {
            unset($STREETS[$streetName]);
            unset($street->end->streetsIn[$streetName]);
            unset($street->end->semaphoreTime[$streetName]);
        }
    }

    foreach ($INTERSECTIONS as $intersectionId => $intersection) {
        if ($intersection->streetsIn === 1) {
            $intersection->fixedGreen = true;
            $fixedIntersections[$intersectionId] = $intersection;
            unset($variableIntersections[$intersectionId]);
        }
    }

    // Run init

    foreach ($INTERSECTIONS as $intersectionId => $intersection) {
        $intersection->updateScheduling();
    }

    for ($t = 0; $t < $DURATION; $t++) {
        //Log::out("Step: $t");

        foreach ($CARS as $carId => $car) {
            if ($car->nextStep()) {
                $SCORE += $BONUS + ($DURATION - $t);
                unset($CARS[$carId]);
            }
        }

        foreach ($INTERSECTIONS as $intersectionId => $intersection) {
            $intersection->nextStep($t);
        }
    }

    foreach ($INTERSECTIONS as $intersectionId => $intersection) {
        $max = 1;
        $min = 100000;
        foreach ($intersection->streetsIn as $streetName => $street) {
            if ($street->maxQueue > $max) {
                $max = $street->maxQueue;
            }
            if ($street->maxQueue < $min) {
                $min = $street->maxQueue;
            }
        }
        foreach ($intersection->streetsIn as $streetName => $street) {
            $semaphoreTimes[$streetName] = ceil(pow($street->maxQueue, 0.5) / $min);
        }
    }

    Log::out("Score: $SCORE");

}

/* OUTPUT */
Log::out('Output...');
$output = "xxx";

foreach ($INTERSECTIONS as $iid => $i) {
    if (!$i->streetsIn) unset($INTERSECTIONS[$iid]);
}

$file = [];
$file[] = count($INTERSECTIONS);
foreach ($INTERSECTIONS as $i) {
    $file[] = $i->id;
    $file[] = count($i->streetsIn);
    foreach ($i->semaphoreTime as $streetName => $time) {
        $file[] = "$streetName $time";
    }
}

$fileManager->outputV2(implode("\n", $file), 'score_' . $SCORE);
//Autoupload::submission($fileName, null, $output);
