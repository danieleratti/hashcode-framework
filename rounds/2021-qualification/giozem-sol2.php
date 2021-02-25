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
Cerberus::runClient(['fileName' => 'e' /*, 'param1' => 1.0*/]);
Autoupload::init();

include 'dr-reader.php';


/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Street[] $STREETS */
/** @var Intersection[] $INTERSECTIONS */
/** @var Car[] $CARS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

$SCORE = 0;


/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = $param1;

foreach ($INTERSECTIONS as $i){
    if(count($i->streetsIn) > 2) {
        Log::out($i->id);
    }
}


/* OUTPUT */
Log::out('Output...');
//$output = count($INTERSECTIONS) . PHP_EOL;
//foreach ($INTERSECTIONS as $intersection) {
//    $output .= $intersection->id . PHP_EOL;
//    $output .= count($intersection->semaphoreToTime) . PHP_EOL;
//    foreach ($intersection->semaphoreToTime as $semaphoreId => $time) {
//        $output .= $semaphoreId . " " . $time . PHP_EOL;
//    }
//}
//$fileManager->outputV2($output, 'score_' . $SCORE);
//Autoupload::submission($fileName, null, $output);
