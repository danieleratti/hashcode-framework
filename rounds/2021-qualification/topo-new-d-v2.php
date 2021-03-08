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
Cerberus::runClient(['fileName' => 'd', 'kStoppingCarsDurationMultiplier' => $kStoppingCarsDurationMultiplier]);
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
    global $CARS, $INTERSECTIONS, $STREETS, $DURATION, $kStoppingCarsDurationMultiplier, $BONUS;
    $car2duration = [];
    $street2points = [];
    $semaphores = [];
    $street2startingCars = [];
    $street2startingCarsHeavy = [];
    $car2durationFromHeavyIntersection = []; // utile anche per il D?

    foreach ($CARS as $car) {
        $duration = 0;
        foreach ($car->streets as $idx => $street) {
            if ($idx > 0) {
                $duration += $street->duration;
            }
            if ($idx < count($car->streets) - 1) {
                $duration += count($street->stoppingCars) / $DURATION * $kStoppingCarsDurationMultiplier;
            }
        }
        $street2startingCars[$car->startingStreet->name][] = $car->id;
        $car2duration[$car->id] = $duration;
    }

    foreach ($STREETS as $street) {
        $duration = 0;
        $points = 0;
        foreach ($street->stoppingCars as $car) {
            $duration += $car2duration[$car->id];
            $points = $BONUS + $DURATION - $car2duration[$car->id];
        }
        $street2points[$street->name] = $points; // TUNE THIS
        //$street2points[$street->name] = count($street->stoppingCars); // TUNE THIS
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
                $_semaphores[] = ['streetName' => $streetName, 'duration' => ceil($share / $maxVal * $ratio), 'startingScore' => count($street2startingCars[$streetName]), 'startingCars' => count($street2startingCars[$streetName])];
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


function trySwitch(&$cfg, $T, $bestStreetInName)
{
    if (!in_array($bestStreetInName, $cfg))
        return false;
    $streetB = $bestStreetInName;
    $idxA = $T % count($cfg);
    $streetA = $cfg[$idxA];
    $idxB = array_search($streetB, $cfg);

    $idxA_from = $idxA;
    $idxA_to = $idxA;
    $idxB_from = $idxB;
    $idxB_to = $idxB;
    for ($i = $idxA; $i >= 0; $i--) {
        if ($cfg[$i] == $streetA)
            $idxA_from = $i;
        else
            break;
    }
    for ($i = $idxA; $i < count($cfg); $i++) {
        if ($cfg[$i] == $streetA)
            $idxA_to = $i;
        else
            break;
    }
    for ($i = $idxB; $i >= 0; $i--) {
        if ($cfg[$i] == $streetB)
            $idxB_from = $i;
        else
            break;
    }
    for ($i = $idxB; $i < count($cfg); $i++) {
        if ($cfg[$i] == $streetB)
            $idxB_to = $i;
        else
            break;
    }
    $lenA = $idxA_to - $idxA_from + 1;
    $lenB = $idxB_to - $idxB_from + 1;

    //if($lenA != 1) return false;

    //$cfg[$idxB] = $cfg[$idxA];
    //$cfg[$idxA] = $streetB;

    if ($lenA != $lenB)
        return false; // first simple version

    $cutA = array_slice($cfg, $idxA_from, $lenA);
    $cutB = array_slice($cfg, $idxB_from, $lenB);
    array_splice($cfg, $idxA_from, $lenA, $cutB);
    array_splice($cfg, $idxB_from, $lenB, $cutA);

    //Log::out("Switch $streetA <-> $streetB ($lenA)");

    return true;
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

    $car2durationFromHeavyIntersection = $inputSemaphores['car2durationFromHeavyIntersection'];

    foreach ($STREETS as $street) {
        $semaphoreQueues[$street->name] = [];
    }

    foreach ($CARS as $car) {
        $T2EnqueueSemaphore[0][0][$car->id] = $car->startingStreet->name;
    }

    foreach ($inputSemaphores['semaphores'] as $intersectionId => $mutex) {
        foreach ($mutex as $streetName => $num) {
            for ($i = 0; $i < $num; $i++) {
                $intersectionId2T2greenStreet[$intersectionId][] = $streetName;
            }
        }
    }

    for ($T = 0; $T < $DURATION + 1; $T++) {
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
            if (isset($intersectionId2T2greenStreet[$intersection->id])) {
                $cycledT = $T % count($intersectionId2T2greenStreet[$intersection->id]);
                $greenStreetName = $intersectionId2T2greenStreet[$intersection->id][$cycledT];

                if (!$usedStreets[$greenStreetName]) {
                    //Log::out("$T) Int" . $intersection->id);
                    $unusedStreet2points = [];
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (!$usedStreets[$streetIn->name] && count($semaphoreQueues[$streetIn->name]) > 0) {
                            $points = 0;
                            foreach ($semaphoreQueues[$streetIn->name] as $carId) {
                                $points += 1;
                            }
                            $unusedStreet2points[] = ['streetName' => $streetIn->name, 'points' => $points];
                        }
                    }
                    ArrayUtils::array_keysort($unusedStreet2points, 'points', 'DESC');
                    foreach ($unusedStreet2points as $unusedStreet) {
                        if ($unusedStreet['streetName'] == $greenStreetName) {
                            break;
                        }
                        if (trySwitch($intersectionId2T2greenStreet[$intersection->id], $T, $unusedStreet['streetName'])) {
                            $greenStreetName = $unusedStreet['streetName'];
                            break;
                        }
                    }
                }

                if (count($semaphoreQueues[$greenStreetName]) > 0) {
                    $usedStreets[$greenStreetName] = true;
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
    }

    $__config = [];
    foreach ($intersectionId2T2greenStreet as $mutexId => $mutex) {
        foreach ($mutex as $streetName) {
            $__config[$mutexId][$streetName] += 1;
        }
    }

    return [
        //'semaphores' => $inputSemaphores['semaphores'],
        'semaphores' => $__config,
        'score' => $score,
        'early' => $early,
        'bonus' => $bonus
    ];
}

$semaphores = getFirstSemaphores();
$configScore = getSimulation($semaphores);
Log::out("SCORE($fileName) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
$fileManager->outputV2(getOutput($configScore['semaphores']), '_new_' . $fileName . '_' . $configScore['score']);
//Autoupload::submission($fileName, null, getOutput($configScore['semaphores']));
