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
Cerberus::runClient(['fileName' => 'b', 'bestCarsPerc' => '1', 'cycleMaxDuration' => '1']); // Cerberus::runClient(['fileName' => 'b', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 1]);
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

function trySwitch(&$cfg, $T, $bestStreetInName)
{
    //trySwitch($_config[$intersection->id], $T, $bestStreetInName);
    $idxCurrent = $T % count($cfg);
    $idxSearched = array_search($bestStreetInName, $cfg);
    $cfg[$idxSearched] = $cfg[$idxCurrent];
    $cfg[$idxCurrent] = $bestStreetInName;
    return true;
}

function getScore($config) // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];
{
    global $CARS, $STREETS, $INTERSECTIONS, $DURATION, $BONUS;
    $score = 0;
    $bonusScore = 0;
    $earlyScore = 0;
    $tToCarAtSemaphore = []; // [StepT] => [ car1 => StreetName1, car2 => StreetName2 ]
    $streetSemaphoreQueue = []; // [StreetName] => [car1, car2, car3]
    $_config = [];
    $avgIntersectionsQueues = [];
    $avgStreetsWaitingTime = [];
    $carScores = [];
    $usedStreets = [];

    foreach ($STREETS as $street) {
        $streetSemaphoreQueue[$street->name] = [];
    }

    foreach ($config as $mutexId => $mutex) {
        foreach ($mutex as $streetName => $num) {
            for ($i = 0; $i < $num; $i++) {
                $_config[$mutexId][] = $streetName;
            }
        }
    }

    // heating
    foreach ($CARS as $car) {
        $tToCarAtSemaphore[0][$car->id] = $car->startingStreet->name;
    }

    for ($T = 0; $T < $DURATION; $T++) {
        if ($T % 100 == 0) {
            Log::out("getScore T = $T/$DURATION (score = $score)");
        }

        foreach ($tToCarAtSemaphore[$T] as $carId => $streetName) {
            //if(!@$streetSemaphoreQueue[$streetName])
            //    $streetSemaphoreQueue[$streetName] = [];
            array_unshift($streetSemaphoreQueue[$streetName], $carId);
        }
        unset($tToCarAtSemaphore[$T]);

        foreach ($INTERSECTIONS as $intersection) {
            if (count($_config[$intersection->id]) > 0) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                // if is there queue
                if (count($streetSemaphoreQueue[$streetName]) == 0 && !$usedStreets[$streetName]) {
                    $bestStreetInName = null;
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (!$usedStreets[$streetIn->name] && count($streetSemaphoreQueue[$streetIn->name]) > 0) {
                            $bestStreetInName = $streetIn->name;
                            //Log::out("Switch $streetName <-> $bestStreetInName");
                            if($config[$intersection->id][$bestStreetInName] == 1 && $config[$intersection->id][$streetName] == 1) { // mod simple (only 1)
                                if (trySwitch($_config[$intersection->id], $T, $bestStreetInName)) {
                                    $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                                    break;
                                } else
                                    continue;
                            }
                        }
                    }
                }
                if (count($streetSemaphoreQueue[$streetName])) {
                    $usedStreets[$streetName] = true;
                    // go ahead (only 1)
                    $carId = array_pop($streetSemaphoreQueue[$streetName]);
                    $streetIdxForCar = $CARS[$carId]->streetName2IDX[$streetName];
                    $nextStreet = $CARS[$carId]->streets[$streetIdxForCar + 1];
                    if ($streetIdxForCar == count($CARS[$carId]->streets) - 2) {
                        // fine corsa alla fine della prossima strada
                        $tEnd = $T + 1 + $nextStreet->duration;
                        if ($tEnd < $DURATION) {
                            $_earlyScore = ($DURATION - $tEnd + 1);
                            $bonusScore += $BONUS;
                            $earlyScore += $_earlyScore;
                            $carScores[$carId] = $BONUS + $_earlyScore;
                            $score += $carScores[$carId];
                        }
                    } else {
                        // next semaphore
                        $tToCarAtSemaphore[$T + $nextStreet->duration][$carId] = $nextStreet->name;
                    }
                }
            }
            /*foreach($intersection->streetsIn as $streetIn) {
                $avgIntersectionsQueues[$intersection->id][$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                $avgIntersectionsQueues[$intersection->id]['total'] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                $avgStreetsWaitingTime[$streetIn->name]  += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
            }*/
        }

    }

    /*// TODO stavo facendo qui sotto
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
    }*/

    $__config = [];
    foreach ($_config as $mutexId => $mutex) {
        foreach ($mutex as $streetName) {
            $__config[$mutexId][$streetName] += 1;
        }
    }


    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'carScores' => $carScores,
        'config' => $__config,
        //'avgIntersectionsQueues' => $avgIntersectionsQueues,
        //'avgStreetsWaitingTime' => $avgStreetsWaitingTime,
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
        //$maxPoints += $DURATION - $car->pathDuration - $lostPoints;
        //$maxPoints = $BONUS + $DURATION - $car->pathDuration - 5 * (count($car->streets) - 1);
        $maxPoints = 1 / $car->pathDuration;
        $cars[] = ['car' => $car, 'maxPoints' => $maxPoints];
    }
    ArrayUtils::array_keysort($cars, 'maxPoints', 'DESC');

    $semaphores = [];
    $streetsPoints = [];
    $streetsStartingPoints = [];
    for ($i = 0; $i < count($cars) * $bestCarsPerc; $i++) {
        $car = $cars[$i];
        foreach ($car['car']->streets as $idx => $street) {
            if ($idx < count($car['car']->streets) - 1) {
                $streetsPoints[$street->name] += max(0, $car['maxPoints']);
                if ($idx == 0) {
                    //$streetsStartingPoints[$street->name] += max(0, $car['maxPoints']); //TODO test also 1
                    $streetsStartingPoints[$street->name] += 1; //TODO test also 1
                }
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
            //arsort($streetsInDuration);
            uksort($streetsInDuration, function ($a, $b) use ($streetsInDuration, $streetsStartingPoints) {
                return $streetsStartingPoints[$a] < $streetsStartingPoints[$b];
            });
            /*if(count($streetsInDuration) > 3) {
                print_r($streetsInDuration);
                foreach($streetsInDuration as $k => $v) {
                    echo "$k => ".$streetsStartingPoints[$k]." starting points\n";
                }
                die();
            }*/
            $semaphores[$intersection->id] = $streetsInDuration;
        }
    }

    return $semaphores;
}

function getSemaphoresV2($carScores, $cycleMaxDuration = 5)
{
    global $CARS, $DURATION, $BONUS, $STREETS, $INTERSECTIONS;
    $cars = [];
    foreach ($CARS as $car) {
        $maxPoints = $carScores[$car->id];
        if ($maxPoints == 0) {
            foreach ($car->streets as $street) {
                $maxPoints -= $street->nSemaphorePassingCars;
            }
        }
        $cars[] = ['car' => $car, 'maxPoints' => $maxPoints];
    }
    ArrayUtils::array_keysort($cars, 'maxPoints', 'DESC');

    $semaphores = [];
    $streetsPoints = [];
    for ($i = 0; $i < count($cars); $i++) {
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

foreach ($initialStreetsWaitingTime as $k => $v) {
    $initialStreetsWaitingTime[$k] += 4.0;
}

$cycleMaxDuration = min($DURATION, $cycleMaxDuration);

$semaphores = getSemaphores($initialStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
$fileManager->outputV2(getOutput($configScore['config']), '_d_score_' . $configScore['score']);
//Autoupload::submission($fileName, null, getOutput($semaphores));

/*
for ($i = 0; $i < 10; $i++) {
    $semaphores = getSemaphoresV2($configScore['carScores'], $cycleMaxDuration);
    $configScore = getScore($semaphores);
    Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']}");
    $fileManager->outputV2(getOutput($semaphores), '_d_score_' . $configScore['score']);
}
*/


/*$semaphores = getSemaphores($configScore['avgStreetsWaitingTime'], $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']}");
$fileManager->outputV2(getOutput($semaphores), '_2nd_score_' . $configScore['score']);
*/


/* OUTPUT */
/*
Log::out('Output...');
$fileManager->outputV2($output, 'time_' . time());
Autoupload::submission($fileName, null, $output);
Log::out("Fine $fileName $EXP $MAXCYCLEDURATION $OVERHEADQUEUE $BESTPERC");
*/
