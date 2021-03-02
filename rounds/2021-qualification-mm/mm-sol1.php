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
Cerberus::runClient(['fileName' => 'c' /*, 'param1' => 1.0*/]);
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

function forgetStreet(Street $street)
{
    global $STREETS;
    unset($STREETS[$street->name]);
    if ($street->end) {
        if (count($street->end->streetsIn) === 0) {
            forgetIntersection($street->end);
        }
        unset($street->end->streetsIn[$street->name]);
    }
    if ($street->start) {
        if (count($street->start->streetsOut) === 0) {
            forgetIntersection($street->start);
        }
        unset($street->start->streetsOut[$street->name]);
    }
}

function forgetIntersection(Intersection $intersection)
{
    global $INTERSECTIONS;
    unset($INTERSECTIONS[$intersection->id]);
    foreach ($intersection->streetsIn as $street) {
        forgetStreet($street);
    }
    foreach ($intersection->streetsOut as $street) {
        forgetStreet($street);
    }
}

/* ALGO */
Log::out("Run with fileName $fileName");

$lastRun = false;
$cycles = 30;

// Remove streets with no usage
foreach ($STREETS as $streetName => $street) {
    if ($street->usage === 0) {
        forgetStreet($street);
    }
}

for ($o = 0; $o < $cycles; $o++) {

    // Variables init
    $isLastRun = $o == $cycles - 1;
    $score = 0;
    /** @var Intersection[] $activeIntersections */
    $activeIntersections = [];
    /** @var Car[] $activeCars */
    $activeCars = $CARS;

    // Objects init
    foreach ($INTERSECTIONS as $intersection) {
        $intersection->init();
    }
    foreach ($STREETS as $street) {
        $street->init();
    }
    foreach ($CARS as $car) {
        $car->init();
    }

    // Calculate scheduling
    foreach ($INTERSECTIONS as $intersectionId => $intersection) {
        $intersection->calculateScheduling();
    }

    for ($t = 0; $t <= $DURATION; $t++) {
        //Log::out("t: $t");

        // Cars movements
        foreach ($activeCars as $carId => $car) {
            $carState = $car->nextStep();
            if ($carState === Car::STATE_JUST_ENQUEUED) {
                $activeIntersections[$car->currentStreet->end->id] = $car->currentStreet->end;
                if ($car->currentStreetIdx === count($car->streets) - 1) { // Era l'ultima strada
                    $score += $BONUS + ($DURATION - $t);
                    unset($activeCars[$carId]);
                }
            }
        }

        // Intersection handling
        foreach ($activeIntersections as $intersectionId => $intersection) {
            if ($intersection->nextStep($t)) {
                if (count($intersection->remainingCars) === 0)
                    unset($activeIntersections[$intersectionId]);
            }

        }

        // Semaphores update
        foreach ($STREETS as $street) {
            $street->semaphore->update();
        }
    }

    // Save history
    /*foreach ($INTERSECTIONS as $intersection) {
        $intersection->init();
    }*/
    foreach ($INTERSECTIONS as $intersection) {
        $intersection->calculateTimeDuration();
    }
    /*foreach ($STREETS as $street) {
        $street->semaphore->saveHistory();
    }*/
    /*foreach ($CARS as $car) {
        $car->init();
    }*/

    /*
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
    */

    Log::out("Score: $score");

}

/* OUTPUT */
Log::out('Output...');

foreach ($INTERSECTIONS as $iid => $i) {
    if (!$i->streetsIn) unset($INTERSECTIONS[$iid]);
}

$file = [];
$file[] = count($INTERSECTIONS);
foreach ($INTERSECTIONS as $i) {
    $file[] = $i->id;
    $file[] = count($i->streetsIn);
    foreach ($i->streetsIn as $streetName => $street) {
        $file[] = "$streetName {$street->semaphore->timeDuration}";
    }
}

$fileManager->outputV2(implode("\n", $file), 'score_' . $score);
//Autoupload::submission($fileName, null, $output);
