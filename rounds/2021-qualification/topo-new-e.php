<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'e';
$OVERHEADQUEUE = 0;
$kStoppingCarsDurationMultiplier = 1.0;
Cerberus::runClient(['fileName' => 'e', 'kStoppingCarsDurationMultiplier' => $kStoppingCarsDurationMultiplier]);
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
    global $initialStreetsWaitingTime;
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

        $t = $T;
        //if($T > 0)
        //    $t--;
        ksort($tToCarAtSemaphore[$t]);
        foreach ($tToCarAtSemaphore[$t] as $carId => $streetName) {
            //if(!@$streetSemaphoreQueue[$streetName])
            //    $streetSemaphoreQueue[$streetName] = [];
            array_unshift($streetSemaphoreQueue[$streetName], $carId);
            //$streetSemaphoreQueue[$streetName][] = $carId;
        }
        unset($tToCarAtSemaphore[$t]);

        foreach ($INTERSECTIONS as $intersection) {
            if (count($_config[$intersection->id]) > 0) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                // if is there queue
                if (count($streetSemaphoreQueue[$streetName]) == 0 && !$usedStreets[$streetName]) {
                    /*$bestStreetInName = null;
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (!$usedStreets[$streetIn->name] && count($streetSemaphoreQueue[$streetIn->name]) > 0) {
                            $bestStreetInName = $streetIn->name;
                            //Log::out("Switch $streetName <-> $bestStreetInName");
                            //if($config[$intersection->id][$bestStreetInName] == 1 && $config[$intersection->id][$streetName] == 1) { // mod simple (only 1)
                                if (trySwitch($_config[$intersection->id], $T, $bestStreetInName)) {
                                    $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                                    break;
                                } else
                                    continue;
                            //}
                        }
                    }*/
                    $bestStreetInNames = [];
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (!$usedStreets[$streetIn->name] && count($streetSemaphoreQueue[$streetIn->name]) > 0) { // TODO: Search the best!!! Priority order!
                            //$bestStreetInNames[$streetIn->name] = count($streetSemaphoreQueue[$streetIn->name]);
                            $bestStreetInNames[$streetIn->name] = $initialStreetsWaitingTime[$streetIn->name];
                        }
                    }
                    asort($bestStreetInNames);
                    foreach ($bestStreetInNames as $bestStreetInName => $count) {
                        if (trySwitch($_config[$intersection->id], $T, $bestStreetInName))
                            $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
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
            //TODO:
            foreach ($intersection->streetsIn as $streetIn) {
                //$avgIntersectionsQueues[$intersection->id][$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                //$avgIntersectionsQueues[$intersection->id]['total'] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                //$avgStreetsWaitingTime[$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                if (count($streetSemaphoreQueue[$streetIn->name]) > 0) {
                    $avgStreetsWaitingTime[$streetIn->name]['N'] += count($streetSemaphoreQueue[$streetIn->name]);
                    $avgStreetsWaitingTime[$streetIn->name]['D'] += 1;
                }
            }
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

    $_avgStreetsWaitingTime = [];
    foreach ($avgStreetsWaitingTime as $street => $v) {
        $_avgStreetsWaitingTime[$street] = $v['N'] / $v['D'];
    }

    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'carScores' => $carScores,
        'config' => $__config,
        //'avgIntersectionsQueues' => $avgIntersectionsQueues,
        'avgStreetsWaitingTime' => $_avgStreetsWaitingTime,
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
        //$maxPoints = 1 / $car->pathDuration;
        //$maxPoints = pow($BONUS, 1) / pow($car->pathDuration, 2);
        $maxPoints = $BONUS + $DURATION - $car->pathDuration - pow(count($car->streets), 1.5);
        $cars[] = ['car' => $car, 'maxPoints' => $maxPoints];
    }
    ArrayUtils::array_keysort($cars, 'maxPoints', 'ASC');

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
                //$streetsPoints[$street->name] += $car['car']->pathDuration;
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

function getFirstSemaphores()
{
    global $CARS, $INTERSECTIONS, $STREETS, $DURATION, $kStoppingCarsDurationMultiplier;
    $car2duration = [];
    $heavyIntersectionId = 499;
    $car2heavy = [];
    $street2points = [];
    $semaphores = [];
    $street2startingCars = [];
    $street2startingCarsHeavy = [];
    $car2durationFromHeavyIntersection = []; // utile anche per il D?

    foreach ($CARS as $car) {
        $duration = 0;
        $durationToRemoveForHeavy = 0;
        $passingHeavy = false;
        foreach ($car->streets as $idx => $street) {
            if ($idx > 0) {
                $duration += $street->duration;
            }
            if ($idx < count($car->streets) - 1) {
                $duration += count($street->stoppingCars) / $DURATION * $kStoppingCarsDurationMultiplier;
                if ($street->end->id == $heavyIntersectionId) {
                    $passingHeavy = true;
                    $durationToRemoveForHeavy = $duration;
                }
            }
        }
        $street2startingCars[$car->startingStreet->name][] = $car->id;
        if ($passingHeavy) {
            $street2startingCarsHeavy[$car->startingStreet->name][] = $car->id;
            $car2durationFromHeavyIntersection[$car->id] = $duration - $durationToRemoveForHeavy;
        }
        $car2heavy[$car->id] = $passingHeavy;
        $car2duration[$car->id] = $duration;
    }

    foreach ($STREETS as $street) {
        $duration = 0;
        foreach ($street->stoppingCars as $car) {
            $duration += $car2duration[$car->id];
        }
        //$street2points[$street->name] = $duration; // TUNE THIS
        $street2points[$street->name] = count($street->stoppingCars); // TUNE THIS
    }

    foreach ($INTERSECTIONS as $intersection) {
        $totalPoints = 0;
        $streetName2share = [];
        foreach ($intersection->streetsIn as $streetIn) {
            $totalPoints += $street2points[$streetIn->name];
        }
        foreach ($intersection->streetsIn as $streetIn) {
            $share = $street2points[$streetIn->name] / $totalPoints;
            if ($share > 0)
                $streetName2share[$streetIn->name] = $share;
        }
        if (count($streetName2share) == 1) {
            $semaphores[$intersection->id] = [array_key_first($streetName2share) => 1];
        } else {
            // devo sortare per quante partono da quegli startingPoints in modo che al primo giro si parta subito!
            arsort($streetName2share);
            $_semaphores = [];
            $minVal = $streetName2share[array_key_last($streetName2share)];
            $maxVal = $streetName2share[array_key_first($streetName2share)];
            $ratio = min(10, round($maxVal / $minVal));
            foreach ($streetName2share as $streetName => $share)
                $_semaphores[] = ['streetName' => $streetName, 'duration' => ceil($share / $maxVal * $ratio), 'startingScore' => count($street2startingCarsHeavy[$streetName]) * 100 + count($street2startingCars[$streetName]), 'heavyStartingCars' => count($street2startingCarsHeavy[$streetName]), 'startingCars' => count($street2startingCars[$streetName])];
            ArrayUtils::array_keysort($_semaphores, 'startingScore', 'DESC');
            foreach ($_semaphores as $_semaphore) {
                //$semaphores[$intersection->id][$_semaphore['streetName']] = $_semaphore['duration'];
                $semaphores[$intersection->id][$_semaphore['streetName']] = max($_semaphore['duration'], $_semaphore['startingCars']);
            }
        }
    }
    foreach ($semaphores as $intersectionId => $streets) {
        $notTwo = false;
        foreach ($streets as $streetName => $duration) {
            if ($duration != 2)
                $notTwo = true;
        }
        if (!$notTwo) {
            foreach ($streets as $streetName => $duration) {
                $semaphores[$intersectionId][$streetName] = 1.0;
            }
        }
    }
    return [
        'semaphores' => $semaphores,
        'car2durationFromHeavyIntersection' => $car2durationFromHeavyIntersection,
    ];
}

function getSimulation($inputSemaphores)
{
    global $CARS, $INTERSECTIONS, $STREETS, $DURATION, $BONUS;
    $score = 0;
    $early = 0;
    $bonus = 0;

    $T2Exit = []; // [2] => [carId1]
    $T2EnqueueSemaphore = []; // [2] => [$TEnqueued => [carId1 => StreetName]] -> ksort(carId) ?
    $semaphoreQueues = []; // [streetName1] => array_unshift -> [carId1, carId2, carId3] <- array_pop
    $intersectionId2T2greenStreet = [];
    $usedStreets = []; // [StreetName] => true, ...

    foreach ($STREETS as $street) {
        $semaphoreQueues[$street->name] = [];
    }

    foreach ($CARS as $car) {
        $T2EnqueueSemaphore[0][0][$car->id] = $car->startingStreet->name;
    }

    foreach ($inputSemaphores as $intersectionId => $mutex) {
        foreach ($mutex as $streetName => $num) {
            for ($i = 0; $i < $num; $i++) {
                $intersectionId2T2greenStreet[$intersectionId][] = $streetName;
            }
        }
    }

    for ($T = 0; $T < $DURATION+1; $T++) {
        if (true || $T % 100 == 0) {
            Log::out("getScore T = $T/$DURATION (score = $score)");
        }

        // accodo le macchine ai semafori
        foreach ($T2EnqueueSemaphore[$T] as $tEnqueued => $data) {
            ksort($data); //?
            foreach ($data as $carId => $streetName) {
                array_unshift($semaphoreQueues[$streetName], $carId);
            }
        }

        // calcolo i punti delle uscenti
        foreach ($T2Exit[$T] as $carId) {
            $_early = $DURATION - $T;
            $_bonus = $BONUS;
            $early += $_early;
            $bonus += $_bonus;
            $score += $_bonus + $_early;
        }

        // ciclo tutti gli incroci
        foreach ($INTERSECTIONS as $intersection) {
            $greenStreetName = $intersectionId2T2greenStreet[$intersection->id][$T % count($intersectionId2T2greenStreet[$intersection->id])];
            if (count($semaphoreQueues[$greenStreetName]) > 0) {
                $carId = array_pop($semaphoreQueues[$greenStreetName]);

                $streetIdxForCar = $CARS[$carId]->streetName2IDX[$greenStreetName];
                $nextStreet = $CARS[$carId]->streets[$streetIdxForCar + 1];
                if ($streetIdxForCar == count($CARS[$carId]->streets) - 2) {
                    // fine corsa alla fine della prossima strada
                    $tEnd = $T + $nextStreet->duration; //? c'era +1 prima...
                    $T2Exit[$tEnd][] = $carId;
                } else {
                    // next semaphore
                    $T2EnqueueSemaphore[$T + $nextStreet->duration][$T][$carId] = $nextStreet->name;
                }

            }
        }
    }

    return [
        'semaphores' => $inputSemaphores,
        'score' => $score,
        'early' => $early,
        'bonus' => $bonus
    ];
}

$semaphores = getFirstSemaphores();
$configScore = getSimulation($semaphores['semaphores']);
Log::out("SCORE($fileName) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
$fileManager->outputV2(getOutput($configScore['semaphores']), '_new_' . $fileName . '_' . $configScore['score']);
//Autoupload::submission($fileName, null, getOutput($semaphores['semaphores']));
