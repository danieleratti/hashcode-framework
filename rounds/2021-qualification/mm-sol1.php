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
Cerberus::runClient(['fileName' => 'a' /*, 'param1' => 1.0*/]);
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
$SCORE = 0;

$fixedIntersections = [];
$variableIntersections = $INTERSECTIONS;

// Init

foreach ($STREETS as $streetName => $street) {
    if ($street->usage === 0) {
        unset($STREETS[$streetName]);
        unset($street->end->streetsIn[$streetName]);
    }
}

foreach ($INTERSECTIONS as $intersectionId => $intersection) {
    if ($intersection->streetsIn === 1) {
        $intersection->fixedGreen = true;
        $fixedIntersections[$intersectionId] = $intersection;
        unset($variableIntersections[$intersectionId]);
    }
}

// Execution

for ($t = 0; $t < $DURATION; $t++) {

    foreach ($CARS as $carId => $car) {
        if ($car->nextStep($t)) {
            $SCORE += $BONUS + ($DURATION - $t);
            unset($CARS[$this->id]);
        }
    }

    foreach ($INTERSECTIONS as $intersectionId => $intersection) {
        $intersection->nextStep($t);
    }

}

/* OUTPUT */
Log::out('Output...');
$output = "xxx";
//$fileManager->outputV2($output, 'score_' . $SCORE);
//Autoupload::submission($fileName, null, $output);
