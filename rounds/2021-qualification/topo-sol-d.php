<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'a';
$bestCarsPerc = 1.0;
$cycleMaxDuration = 5;
$OVERHEADQUEUE = 0;
Cerberus::runClient(['fileName' => 'd', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 5]);
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
    $avgStreetsWaitingTime = [];

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
        if ($T % 100 == 0) {
            Log::out("getScore T = $T/$DURATION");
        }

        $carsToNext = [];
        foreach ($streets as $streetName => $streetSteps) {
            $intersectionId = $STREETS[$streetName]->end->id;
            if (count($_config[$intersectionId]) > 0) {
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
                            if (!@$streets[$streetName]['mutex'])
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
            if (count($streetSteps['mutex']) > 0) {
                $avgIntersectionsQueues[$STREETS[$streetName]->end->id][$streetName] += count($streetSteps['mutex']) * 1000 / $DURATION;
                $avgStreetsWaitingTime[$streetName] += count($streetSteps['mutex']) * 1000 * 1000 / $DURATION; //rounding helper
            }
        }
    }

    foreach ($avgStreetsWaitingTime as $k => $v) {
        $waitingMultiplier = ($config[$STREETS[$k]->end->id][$k] / array_sum($config[$STREETS[$k]->end->id]));
        if ($waitingMultiplier > 0)
            $waitingMultiplier = 1 / $waitingMultiplier;
        else
            $waitingMultiplier = 1;
        //Log::out("waitingMultiplier = $waitingMultiplier");
        $avgStreetsWaitingTime[$k] *= $waitingMultiplier;
        $avgStreetsWaitingTime[$k] /= 1000 * 1000; //rounding helper
        //$avgStreetsWaitingTime[$k] += 1; //overhead for semaphores?
        $avgStreetsWaitingTime[$k] -= 1.0; //overhead for semaphores?
    }

    /*arsort($avgStreetsWaitingTime);
    print_r($avgStreetsWaitingTime);
    die();*/
    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'avgIntersectionsQueues' => $avgIntersectionsQueues,
        'avgStreetsWaitingTime' => $avgStreetsWaitingTime,
    ];
}

function getSemaphores($streetsWaitingTime, $bestCarsPerc = 1.0, $cycleMaxDuration = 5)
{
    global $CARS, $DURATION, $BONUS, $STREETS, $INTERSECTIONS;
    $cars = [];
    foreach ($CARS as $car) {
        $maxPoints = $BONUS;
        $lostPoints = 0;
        foreach ($car->streets as $idx => $street) {
            if ($idx < count($car->streets) - 1) {
                $lostPoints += $streetsWaitingTime[$street->name];
            }
        }
        $maxPoints += $DURATION - $car->pathDuration - $lostPoints;
        $cars[] = ['car' => $car, 'maxPoints' => $maxPoints];
    }
    ArrayUtils::array_keysort($cars, 'maxPoints', 'DESC');

    $semaphores = [];
    $streetsPoints = [];
    for ($i = 0; $i < count($cars) * $bestCarsPerc; $i++) {
        $car = $cars[$i];
        foreach ($car['car']->streets as $idx => $street) {
            if ($idx < count($car['car']->streets) - 1) {
                $streetsPoints[$street->name] += max(0, $car['maxPoints']);
            }
        }
    }

    arsort($streetsPoints);

    foreach ($INTERSECTIONS as $intersection) {
        $totalPriorities = 0;
        $streetsInDuration = [];
        foreach ($intersection->streetsIn as $streetIn) {
            if ($streetsPoints[$streetIn->name] > 0) {
                $totalPriorities += $streetsPoints[$streetIn->name];
            }
        }
        foreach ($intersection->streetsIn as $streetIn) {
            if ($streetsPoints[$streetIn->name] > 0) {
                $streetsInDuration[$streetIn->name] = ceil($streetsPoints[$streetIn->name] / $totalPriorities * $cycleMaxDuration);
            }
        }
        if (count($streetsInDuration) > 0) {
            arsort($streetsInDuration);
            $semaphores[$intersection->id] = $streetsInDuration;
        }
    }

    return $semaphores;
}

function getOutput($semaphores)
{
    $output = [];
    $output[] = count($semaphores);
    foreach ($semaphores as $id => $o) {
        $output[] = $id;
        $output[] = count($o);
        foreach ($o as $k => $v) {
            $v = (int)$v;
            $output[] = "$k $v";
        }
    }
    $output = implode("\n", $output);
    return $output;
}

$initialStreetsWaitingTime = [];
foreach ($CARS as $car) {
    foreach ($car->streets as $idx => $street) {
        if ($idx < count($car->streets) - 1) {
            $initialStreetsWaitingTime[$street->name] += 1 / $DURATION;
        }
    }
}

foreach($initialStreetsWaitingTime as $k => $v) {
    $initialStreetsWaitingTime[$k] += 4.0;
}

$cycleMaxDuration = min($DURATION, $cycleMaxDuration);

$semaphores = getSemaphores($initialStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']}");
$fileManager->outputV2(getOutput($semaphores), '_1st_score_' . $configScore['score']);

$semaphores = getSemaphores($configScore['avgStreetsWaitingTime'], $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']}");
$fileManager->outputV2(getOutput($semaphores), '_2nd_score_' . $configScore['score']);



/* OUTPUT */
/*
Log::out('Output...');
$fileManager->outputV2($output, 'time_' . time());
Autoupload::submission($fileName, null, $output);
Log::out("Fine $fileName $EXP $MAXCYCLEDURATION $OVERHEADQUEUE $BESTPERC");
*/
