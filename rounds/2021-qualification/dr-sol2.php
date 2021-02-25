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

// tolgo le car senza percorsi
// calcolo le priorità per ogni auto ( 1 / ( duration +* nStreets) ) [+ avanti: quanto una strada è trafficata]
// intersezioni con solo un ingresso -> sempre verdi

/*
// ciclo $T da 0 a $DURATION
    // ciclo le intersezioni
        // calcolo le queues input in quell'intersezione e decido la priority sommata delle auto in coda
        // scelgo la coda con priorità più alta e accendo il semaforo

    StateManager::carActions();
    // ciclo le car
        // eseguo la next thing se possibile (ovvero non rosso) e ricalcolo la priority (???)
*/

foreach($CARS as $car) {
    $car->calcPriority();
    //$car->nextStep();
}

for($T=0;$T<$DURATION;$T++) {
    foreach($CARS as $car) {
        $car->nextStep();
    }

    foreach($INTERSECTIONS as $intersection) {
        /** @var Intersection $intersection */
        $bestPriority = 0;
        $bestStreet = null;
        foreach($intersection->streetsIn as $streetIn) {
            $priority = $streetIn->getPriority();
            if($priority > $bestPriority) {
                $bestPriority = $priority;
                $bestStreet = $streetIn;
            }
        }
        if($bestStreet) {
            if ($bestStreet->name != $intersection->greenStreet->name) {
                $intersection->setGreen($bestStreet);
            }
        }
    }

    foreach($INTERSECTIONS as $intersection) {
        $intersection->nextStep();
    }
}

/* OUTPUT */
Log::out('Output...');
$output = "xxx";
//$fileManager->outputV2($output, 'score_' . $SCORE);
//Autoupload::submission($fileName, null, $output);
