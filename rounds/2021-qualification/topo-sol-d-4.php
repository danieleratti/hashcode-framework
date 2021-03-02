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
Cerberus::runClient(['fileName' => 'd', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 8]);
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
    $tToCarAtSemaphore = []; // [StepT] => [ car1 => StreetName1, car2 => StreetName2 ]
    $streetSemaphoreQueue = []; // [StreetName] => [car1, car2, car3]
    $_config = [];
    $carScores = [];
    $intersectionsQueuesAtDurationPerc = 0.99;
    $intersectionsQueues = [];

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
        if ($T % 1000 == 0) {
        }

        foreach ($tToCarAtSemaphore[$T] as $carId => $streetName) {
            array_unshift($streetSemaphoreQueue[$streetName], $carId);
        }
        unset($tToCarAtSemaphore[$T]);

        foreach ($INTERSECTIONS as $intersection) {
            if (count($_config[$intersection->id]) > 0) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                // if is there queue
                if (count($streetSemaphoreQueue[$streetName])) {
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
        }

        if ($T == round($DURATION * $intersectionsQueuesAtDurationPerc)) {
            foreach ($INTERSECTIONS as $intersection) {
                foreach ($intersection->streetsIn as $streetIn) {
                    if (count($streetSemaphoreQueue[$streetIn->name]) > 0) {
                        $intersectionsQueues[$intersection->id] += count($streetSemaphoreQueue[$streetIn->name]);
                    }
                }
            }
        }
    }

    arsort($intersectionsQueues);

    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'carScores' => $carScores,
        'intersectionsQueues' => $intersectionsQueues,
    ];
}

function getScoreWithFreedom($config, $freedomIntersectionId) // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];
{
    global $CARS, $STREETS, $INTERSECTIONS, $DURATION, $BONUS;
    $score = 0;
    $bonusScore = 0;
    $earlyScore = 0;
    $tToCarAtSemaphore = []; // [StepT] => [ car1 => StreetName1, car2 => StreetName2 ]
    $streetSemaphoreQueue = []; // [StreetName] => [car1, car2, car3]
    $_config = [];
    $carScores = [];
    $intersectionFreedomStreets = [];

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
        if ($T % 1000 == 0) {
        }

        foreach ($tToCarAtSemaphore[$T] as $carId => $streetName) {
            array_unshift($streetSemaphoreQueue[$streetName], $carId);
        }
        unset($tToCarAtSemaphore[$T]);

        foreach ($INTERSECTIONS as $intersection) {
            if (count($_config[$intersection->id]) > 0) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
                // if is there queue

                if ($intersection->id == $freedomIntersectionId) {
                    $streetName = null;
                    $bestStreetQueue = 0;
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (count($streetSemaphoreQueue[$streetIn->name]) > $bestStreetQueue) {
                            $bestStreetQueue = count($streetSemaphoreQueue[$streetIn->name]);
                            $streetName = $streetIn->name;
                        }
                    }
                }

                echo "StreetName = $streetName\n";
                if (count($streetSemaphoreQueue[$streetName])) {
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
        }

    }

    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        'carScores' => $carScores,
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

function getScoredSimulation($semaphores) // $config[$intersectId] = [0 => [ 'street1' => 1, 'street2' => 2, 'street3' => 3 ]];
{
    global $CARS, $STREETS, $INTERSECTIONS, $DURATION, $BONUS;
    $score = 0;
    $bonusScore = 0;
    $earlyScore = 0;
    $tToCarAtSemaphore = []; // [StepT] => [ car1 => StreetName1, car2 => StreetName2 ]
    $streetSemaphoreQueue = []; // [StreetName] => [car1, car2, car3]
    $_config = []; // $_config[$intersectionId][$T] = $streetName
    $carScores = [];
    $cycled = [];
    $startingPoints = [];

    foreach ($STREETS as $street) {
        $streetSemaphoreQueue[$street->name] = [];
    }

    // heating
    foreach ($CARS as $car) {
        $tToCarAtSemaphore[0][$car->id] = $car->startingStreet->name;
    }

    for ($T = 0; $T < $DURATION; $T++) {
        //if ($T % 100 == 0) {
        Log::out("$T / $DURATION (score = $score)...");
        //}
        foreach ($tToCarAtSemaphore[$T] as $carId => $streetName) {
            array_unshift($streetSemaphoreQueue[$streetName], $carId);
        }
        unset($tToCarAtSemaphore[$T]);

        //print_r($_config[0]);

        foreach ($INTERSECTIONS as $intersection) {

            //$_config = []; // $_config[$intersectionId][$T] = $streetName

            if($cycled[$intersection->id]) {
                $streetName = $_config[$intersection->id][$T % count($_config[$intersection->id])];
            }
            else {
                if ($T > 0 && (count($streetSemaphoreQueue[$_config[$intersection->id][$T - 1]]) > 0)) {
                    // if the street at T-1 is still busy, keep open the semaphore! TUNE THIS!
                    $_config[$intersection->id][$T] = $_config[$intersection->id][$T - 1];
                } else {
                    $skipped = 0;
                    $bestStreetIn = null;
                    $bestStreetQueue = 0;
                    foreach ($intersection->streetsIn as $streetIn) {
                        if (count($streetSemaphoreQueue[$streetIn->name]) > $bestStreetQueue) {
                            if (!in_array($streetIn->name, $_config[$intersection->id])) {
                                // still not used
                                $bestStreetQueue = count($streetSemaphoreQueue[$streetIn->name]);
                                $bestStreetIn = $streetIn->name;
                            } else {
                                $skipped++;
                            }
                        }
                    }

                    if ($bestStreetQueue == 0 && $skipped > 0) {
                        $cycled[$intersection->id] = true;
                        $ft = 0;
                        //print_r($_config[$intersection->id]);
                        foreach($semaphores[$intersection->id] as $streetIn => $repeat) {
                            if(!in_array($streetIn, $_config[$intersection->id])) {
                                for($i=0;$i<$repeat;$i++) {
                                    while(isset($_config[$intersection->id][$ft]) && $_config[$intersection->id][$ft] !== null) $ft++;
                                    $_config[$intersection->id][$ft] = $streetIn;
                                }
                            }
                        }
                        //print_r($_config[$intersection->id]);
                        //die();
                    }

                    if ($bestStreetQueue > 0) {
                        $_config[$intersection->id][$T] = $bestStreetIn;
                        /*if ($T > 0 && $_config[$intersection->id][$T - 1] === null) {
                            $startingPoints[$intersection->id] = $T;
                            for ($t = 0; $t < $T; $t++) {
                                if ($_config[$intersection->id][$t] === null) {
                                    $_config[$intersection->id][$t] = $bestStreetIn;
                                }
                            }
                        }*/
                    } else {
                        /*if ($_config[$intersection->id][$T - 1] !== null) {
                            $_config[$intersection->id][$T] = $_config[$intersection->id][$T - 1];
                        } else {
                            $_config[$intersection->id][$T] = null; // WARNING to this -> when found the first, should apply these last! (see above!!!)
                        }*/
                        $_config[$intersection->id][$T] = null; // WARNING to this -> when found the first, should apply these last! (see above!!!)
                    }
                }
                $streetName = $_config[$intersection->id][$T];
            }

            // if is there queue
            if (count($streetSemaphoreQueue[$streetName])) {
                // go ahead (only 1)
                $carId = array_pop($streetSemaphoreQueue[$streetName]);
                $streetIdxForCar = $CARS[$carId]->streetName2IDX[$streetName];
                //echo "$carId ($streetIdxForCar)\n";
                $nextStreet = $CARS[$carId]->streets[$streetIdxForCar + 1];
                if ($streetIdxForCar == count($CARS[$carId]->streets) - 2) {
                    // fine corsa alla fine della prossima strada
                    $tEnd = $T + 1 + $nextStreet->duration;
                    //echo "ENDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD\n";
                    if ($tEnd < $DURATION) {
                        Log::out("EndCar $carId!");
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
    }

    Log::out("Ending...");

    return [
        'score' => $score,
        'bonus' => $bonusScore,
        'early' => $earlyScore,
        //'carScores' => $carScores,
        //'intersectionsQueues' => $intersectionsQueues,
        //'_config' => $_config,
    ];
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

try {
    $semaphores = getSemaphores($initialStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);
    Log::out("IN");
    $configScore = getScoredSimulation($semaphores);
    Log::out("OUT");
    //print_r($configScore);
    $score = $configScore['score'];
    Log::out("SCORE = $score");
} catch (\Throwable $t) {
    die("E: " . $t->getMessage());
}
die();


$semaphores = getSemaphores($initialStreetsWaitingTime, $bestCarsPerc, $cycleMaxDuration);
$configScore = getScore($semaphores);

print_r($configScore['intersectionsQueues']);
die();
Log::out("SCORE($fileName, $bestCarsPerc, $cycleMaxDuration) = {$configScore['score']} ({$configScore['bonus']} + {$configScore['early']})");
$fileManager->outputV2(getOutput($semaphores), '_d_score_' . $configScore['score']);
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
