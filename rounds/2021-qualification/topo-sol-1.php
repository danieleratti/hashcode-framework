<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'd';
$EXP = 1;
$MAXCYCLEDURATION = 1.9;
$OVERHEADQUEUE = 5;
$BESTPERC = 1.0;
$MAXSTREETS = 100;
Cerberus::runClient(['fileName' => 'f', /*'EXP' => 1.0 , 'MAXCYCLEDURATION' => 1.9,*/ 'OVERHEADQUEUE' => 5, 'MAXSTREETS' => 100]);
Autoupload::init();
include 'topo-reader.php';

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

/* FUNCTIONS */
function getScore($config) // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];
{
    global $CARS, $STREETS, $INTERSECTIONS, $DURATION, $BONUS;
    $score = 0;
    $bonusScore = 0;
    $earlyScore = 0;
    $cars2streetIdx = []; // [0] => 0
    $streets = []; // [0] => [$carId1, $carId2, $carId3], [1] => [$carId4, $carId5], ['mutex'] => [$carId6, $carId7]
    $_config = [];
    $avgIntersectionsQueues = [];

    foreach ($config as $mutexId => $mutex) {
        foreach ($mutex as $streetName => $num) {
            for ($i = 0; $i < $num; $i++) {
                $_config[$mutexId][] = $streetName;
            }
        }
    }

    foreach ($CARS as $car) {
        $cars2streetIdx[$car->id] = 0;
        if (!@$streets[$car->startingStreet->name]['mutex'])
            $streets[$car->startingStreet->name]['mutex'] = [];
        array_unshift($streets[$car->startingStreet->name]['mutex'], $car->id);
    }

    for ($T = 0; $T < $DURATION; $T++) {
        if($T%100==0) {
            Log::out("getScore T = $T/$DURATION");
        }
        //Log::out("T=$T [start]", 0, "red");
        //Log::out(json_encode($streets));

        $carsToNext = [];
        foreach ($streets as $streetName => $streetSteps) {
            $intersectionId = $STREETS[$streetName]->end->id;
            if(count($_config[$intersectionId]) > 0) {
                $intersectionStreet = $_config[$intersectionId][$T % count($_config[$intersectionId])];
                if ($intersectionStreet == $streetName) {
                    // if is there queue
                    if (count($streets[$streetName]['mutex'])) {
                        // go ahead (only 1)
                        $carId = array_pop($streets[$streetName]['mutex']);
                        $carsToNext[] = $carId;
                    }
                }
            }

            if ($STREETS[$streetName]->duration > 1) {
                for ($l = $STREETS[$streetName]->duration - 2; $l >= 0; $l--) {
                    if ($l == $STREETS[$streetName]->duration - 2) {
                        if (count($streets[$streetName][$l]) > 0) {
                            $goingToMutexCars = [];
                            foreach ($streets[$streetName][$l] as $carId) {
                                if ($cars2streetIdx[$carId] < $CARS[$carId]->nStreets - 1) {
                                    $goingToMutexCars[] = $carId;
                                } else {
                                    //if ($T + 1 < $DURATION) {
                                        $points = $BONUS + ($DURATION - $T - 1);
                                        $bonusScore += $BONUS;
                                        $earlyScore += ($DURATION - $T - 1);
                                        //Log::out("#Scoring $points for $carId @ $T=$T", 0, "green");
                                        $score += $points;
                                    //}
                                }
                            }
                            if(!@$streets[$streetName]['mutex'])
                                $streets[$streetName]['mutex'] = [];
                            $streets[$streetName]['mutex'] = array_merge($goingToMutexCars, $streets[$streetName]['mutex']);
                        }
                    }
                    if ($l > 0)
                        $streets[$streetName][$l] = $streets[$streetName][$l - 1];
                }
                $streets[$streetName][0] = [];
            }
        }

        foreach ($carsToNext as $carId) {
            $cars2streetIdx[$carId]++;
            $nextStreet = $CARS[$carId]->streets[$cars2streetIdx[$carId]];
            if ($nextStreet->duration == 1) {
                if ($cars2streetIdx[$carId] == $CARS[$carId]->nStreets - 1) {
                    if ($T + 1 < $DURATION - 1) {
                        $points = $BONUS + ($DURATION - $T - 2);
                        $bonusScore += $BONUS;
                        $earlyScore += ($DURATION - $T - 2);
                        //Log::out("*Scoring $points for $carId @ $T=$T", 0, "green");
                        $score += $points;
                    }
                } else {
                    if (!@$streets[$nextStreet->name]['mutex'])
                        $streets[$nextStreet->name]['mutex'] = [];
                    array_unshift($streets[$nextStreet->name]['mutex'], $carId);
                }
            } else {
                if (!@$streets[$nextStreet->name][0])
                    $streets[$nextStreet->name][0] = [];
                array_unshift($streets[$nextStreet->name][0], $carId);
            }
        }

        //Log::out(json_encode($streets));
        //Log::out("T=$T [end]", 0, "red");

        foreach ($streets as $streetName => $streetSteps) {
            if(count($streetSteps['mutex']) > 0) {
                $avgIntersectionsQueues[$STREETS[$streetName]->end->id][$streetName] += count($streetSteps['mutex']) * 1000 / $DURATION;
            }
        }
    }

    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'avgIntersectionsQueues' => $avgIntersectionsQueues,
    ];
}

//$x = getScore([1 => ['rue-d-athenes' => 2, 'rue-d-amsterdam' => 1], 0 => ['rue-de-londres' => 2], 2 => ['rue-de-moscou' => 1]]);

$config = [];
$f = explode("\n", file_get_contents("output/f.txt/test.txt"));
$intersectionId = null;
$toRead = null;
foreach($f as $k => $v) {
    if($k) {
        if($toRead === 0)
            $intersectionId = null;
        if($intersectionId === null) {
            $intersectionId = (int)$v;
            $toRead = null;
        } elseif($toRead === null) {
            $toRead = (int)$v;
        } else {
            $toRead--;
            list($streetName, $count) = explode(" ", $v);
            $count = (int)$count;
            $config[$intersectionId][$streetName] = $count;
        }
    }
}
$x = getScore($config);

print_r($x);
die();

$SCORE = 0;

$config = []; // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];


die();


/* COLLECTIONS */
/*
$CARS = collect($CARS);
$CARS->keyBy('id');

$STREETS = collect($STREETS);
$STREETS->keyBy('name');

$INTERSECTIONS = collect($INTERSECTIONS);
$INTERSECTIONS->keyBy('id');
*/

/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = 0;



/* OUTPUT * /
Log::out('Output...');
$output = [];
$output[] = count($OUTPUT);
foreach($OUTPUT as $id => $o) {
    $output[] = $id;
    $output[] = count($o);
    foreach($initialStreets as $s => $nil) {
        foreach ($o as $k => $v) {
            if($k == $s) {
                $v = (int)$v;
                $output[] = "$k $v";
            }
        }
    }
}
$output = implode("\n", $output);
$fileManager->outputV2($output, 'time_' . time());
Autoupload::submission($fileName, null, $output);
Log::out("Fine $fileName $EXP $MAXCYCLEDURATION $OVERHEADQUEUE $BESTPERC");
*/
