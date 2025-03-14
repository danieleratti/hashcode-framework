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
$startingDeltaTime = 3.8;
$kMultiLostScore = 1.0;
//Cerberus::runClient(['fileName' => 'f', 'bestCarsPerc' => '1', 'cycleMaxDuration' => '8', 'startingDeltaTime' => 3.8]); // Cerberus::runClient(['fileName' => 'f', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 8]);
Cerberus::runClient(['fileName' => 'e', 'kMultiLostScore' => 1.0]);
//Cerberus::runClient(['fileName' => 'e', 'bestCarsPerc' => '1.0', 'cycleMaxDuration' => '2.3', 'startingDeltaTime' => 0]);
//Cerberus::runClient(['fileName' => 'e', 'bestCarsPerc' => '0.93', 'cycleMaxDuration' => '7.3', 'startingDeltaTime' => '3.7']);
//Cerberus::runClient(['fileName' => 'f', 'bestCarsPerc' => '1', 'cycleMaxDuration' => '8', 'startingDeltaTime' => 3.8]); // Cerberus::runClient(['fileName' => 'f', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 8]);
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

function trySwitchOld(&$cfg, $T, $bestStreetInName)
{
    //trySwitch($_config[$intersection->id], $T, $bestStreetInName);
    $idxCurrent = $T % count($cfg);
    $idxSearched = array_search($bestStreetInName, $cfg);
    $cfg[$idxSearched] = $cfg[$idxCurrent];
    $cfg[$idxCurrent] = $bestStreetInName;
    return true;
}

function trySwitch(&$cfg, $T, $bestStreetInName)
{
    //trySwitch($_config[$intersection->id], $T, $bestStreetInName);
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

    /*
    if ($lenA != 1) {
        return false;
    }
    */

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

function getScore($config, $fake499 = false) // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];
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

    $carsStatus = [];

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
        $tToCarAtSemaphore[0][] = ['carId' => $car->id, 'streetName' => $car->startingStreet->name];
        $carsStatus[$car->id] = ['streetName' => null];
    }

    $override499 = [];

    for ($T = 0; $T < $DURATION; $T++) {
        $override499[$T] = null;
    }

    for ($T = 0; $T < $DURATION; $T++) {
        if ($T % 100 == 0) {
            //Log::out("getScore T = $T/$DURATION (score = $score)");
        }

        foreach ($tToCarAtSemaphore[$T] as $queue) {
            //if(!@$streetSemaphoreQueue[$streetName])
            //    $streetSemaphoreQueue[$streetName] = [];
            array_unshift($streetSemaphoreQueue[$queue['streetName']], $queue['carId']);
            //$streetSemaphoreQueue[$streetName][] = $carId;
        }
        unset($tToCarAtSemaphore[$T]);

        //$_config[499] = [];

        foreach ($INTERSECTIONS as $intersection) {
            if (count($_config[$intersection->id]) > 0) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                // if is there queue

                if ($fake499 && $intersection->id == 499) {
                    if ($override499[$T] !== null) {
                        $streetName = $override499[$T];
                    } else {
                        $bestStreetInNames = [];
                        foreach ($intersection->streetsIn as $streetIn) {
                            if (count($streetSemaphoreQueue[$streetIn->name]) > 0) {
                                foreach ($streetSemaphoreQueue[$streetIn->name] as $q) {
                                    //if (!$usedStreets[$streetName]) //MOD TO AVOID DUPLI!
                                    $bestStreetInNames[$streetIn->name] += $CARS[$q]->maxScore;
                                    //$bestStreetInNames[$streetIn->name] = max($CARS[$q]->maxScore, $bestStreetInNames[$streetIn->name]);
                                }
                            }
                        }
                        arsort($bestStreetInNames);
                        $streetName = array_key_first($bestStreetInNames);
                        for ($t = $T; $t < $T + count($streetSemaphoreQueue[$streetName]); $t++) {
                            $override499[$t] = $streetName;
                            $_config[499][$t] = $streetName;
                        }
                    }
                }

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
                            //$bestStreetInNames[$streetIn->name] = $initialStreetsWaitingTime[$streetIn->name];
                            foreach ($streetSemaphoreQueue[$streetIn->name] as $q) {
                                $bestStreetInNames[$streetIn->name] += $CARS[$q]->maxScore;
                                //$bestStreetInNames[$streetIn->name] = max($bestStreetInNames[$streetIn->name], $CARS[$q]->maxScore);
                            }
                        }
                    }
                    //arsort($bestStreetInNames);
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
                            unset($carsStatus[$carId]);
                        }
                    } else {
                        // next semaphore
                        $carsStatus[$carId] = ['streetName' => $nextStreet->name, 'streetIdxForCar' => $streetIdxForCar, 'remainingStreets' => count($CARS[$carId]->streets) - 2 - $streetIdxForCar];
                        $tToCarAtSemaphore[$T + $nextStreet->duration][] = ['carId' => $carId, 'streetName' => $nextStreet->name];
                    }
                }
            }
            //TODO:
            foreach ($intersection->streetsIn as $streetIn) {
                //$avgIntersectionsQueues[$intersection->id][$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                //$avgIntersectionsQueues[$intersection->id]['total'] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                //$avgStreetsWaitingTime[$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]) / $DURATION;
                if (count($streetSemaphoreQueue[$streetIn->name]) > 0 && $streetName != $streetIn->name) {
                    $avgIntersectionsQueues[$intersection->id][$streetIn->name] += count($streetSemaphoreQueue[$streetIn->name]);
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
        'avgIntersectionsQueues' => $avgIntersectionsQueues,
        'avgStreetsWaitingTime' => $_avgStreetsWaitingTime,
        'override499' => $override499,
        'carsStatus' => $carsStatus,
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
    //ArrayUtils::array_keysort($cars, 'maxPoints', 'DESC');
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

function getTheoricalSemaphores()
{
    global $STREETS, $CARS, $cars, $intersections;
    foreach ($STREETS as $street) {
        $streetSemaphoreQueue[$street->name] = [];
    }
    foreach ($CARS as $car) {
        array_unshift($streetSemaphoreQueue[$car->startingStreet->name], $car->id);
    }

}

/*
function iterateSemaphores($cars)
{
    $_config = []; // [semaphoreId] => [ streetName1, streetName1, streetName2 ], ...
    foreach($cars as $_car) {
        /** @var Car $car * /
        $car = $_car['car'];

    }
}*/

// ALGO!!!

$cars = [];
foreach ($CARS as $car) {
    $lostScore = 0;
    foreach ($car->streets as $idx => $street) {
        if ($idx > 0)
            $lostScore += $street->duration; //first duration don't count
        if ($idx < count($car->streets) - 1)
            $lostScore += $street->end->nSemaphorePassingCars / $DURATION * $kMultiLostScore;
    }
    $maxScore = $DURATION + $BONUS - $lostScore;
    $car->maxScore = $maxScore;
    $cars[] = ['car' => $car, 'maxScore' => $maxScore];
}

ArrayUtils::array_keysort($cars, 'maxScore', 'DESC');


$intersections = [];
foreach ($INTERSECTIONS as $intersection) {
    $streetsIn = [];
    $importance = 0;
    foreach ($intersection->streetsIn as $streetIn) {
        if ($streetIn->nSemaphorePassingCars > 0) {
            $streetsIn[$streetIn->name] = $streetIn->nSemaphorePassingCars;
            $importance += $streetIn->nSemaphorePassingCars;
        }
    }
    //if (count($streetsIn) > 1)
    $intersections[$intersection->id] = ['intersectionId' => $intersection->id, 'streetsIn' => $streetsIn, 'importance' => $importance];
}

ArrayUtils::array_keysort($intersections, 'importance', 'DESC');


$initialStreetsWaitingTime = [];
foreach ($CARS as $car) {
    foreach ($car->streets as $idx => $street) {
        if ($idx < count($car->streets) - 1) {
            $initialStreetsWaitingTime[$street->name] += 1 / $DURATION;
        }
    }
}


foreach ($initialStreetsWaitingTime as $k => $v) {
    $initialStreetsWaitingTime[$k] += $startingDeltaTime;
}

$cycleMaxDuration = min($DURATION, $cycleMaxDuration);

$semaphores = getSemaphores($initialStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);

/*
$configScore = getScore($semaphores, true);
Log::out("FAKESCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");

$semaphores = $configScore['config'];
$override499 = $configScore['override499'];
$semaphores499 = [];
$skipped = [];
foreach($override499 as $streetName) {
    if(!in_array($streetName, $semaphores499) || $semaphores499[count($semaphores499)-1] == $streetName) {
        $semaphores499[] = $streetName;
    } else {
        $semaphores499[] = $semaphores499[count($semaphores499)-1];
        $skipped[] = $streetName;
    }
}

$semaphores[499] = $semaphores499;
*/
//$configScore = getScore($semaphores, false);

$configScore = getScore($semaphores, false);
Log::out("StartingSCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");

foreach ($semaphores as $semaphoreId => $semaphore) {
    if (count($semaphore) == 2 && array_sum($configScore['avgIntersectionsQueues'][$semaphoreId]) > 0) {
        $firstStreet = array_key_first($semaphore);
        $lastStreet = array_key_last($semaphore);
        $bestNumwaitings = null;
        $bestWaitings = [];
        $bestWaitingsQueues = [];
        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 10; $j++) {
                //$semaphores[7] = ["ejj-h" => $i, "g-h" => $j];
                $semaphores[$semaphoreId] = [$firstStreet => $i, $lastStreet => $j];
                $configScore = getScore($semaphores, false);
                $numWaitings = array_sum($configScore['avgIntersectionsQueues'][$semaphoreId]);
                if (!$bestNumwaitings || $numWaitings < $bestNumwaitings) {
                    $bestNumwaitings = $numWaitings;
                    $bestWaitings = $semaphores[$semaphoreId];
                    $bestWaitingsQueues = $configScore['avgIntersectionsQueues'][$semaphoreId];
                }
            }
        }
        $semaphores[$semaphoreId] = $bestWaitings;
        $configScore = getScore($semaphores, false);
        Log::out("Best for $semaphoreId is " . json_encode($semaphores[$semaphoreId]) . " with queues: " . json_encode($bestWaitingsQueues));
        Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
        $fileManager->outputV2(getOutput($configScore['config']), '_brutealign_' . $fileName . '_score_' . $configScore['score']);
    }
}

/*
$newStreetsWaitingTime = [];
foreach($initialStreetsWaitingTime as $street => $n) {
    $newStreetsWaitingTime[$street] = max($n, $configScore['avgStreetsWaitingTime'][$street]);
}
$semaphores = getSemaphores($newStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
*/

$fileManager->outputV2(getOutput($configScore['config']), '_e_score_' . $configScore['score']);
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
