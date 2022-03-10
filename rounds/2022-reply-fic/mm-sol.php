<?php

use Utils\FileManager;
use Utils\Log;

/** @var string */
global $fileName;
/** @var FileManager */
global $fileManager;

/** @var Demon[] */
global $demons;

/** @var int */
global $initialStamina;
/** @var int */
global $maxStamina;
/** @var int */
global $turnsNumber;
/** @var int */
global $demonsCount;

/** @var int */
global $currentStamina;
/** @var int */
global $currentScore;
/** @var int */
global $t;
/** @var int[] */
global $staminaRecovering;

$fileName = 'b';

/* Reader */
include_once 'mm-reader.php';
//print_r($demons);

/* Functions */

function calculateScoreForSolution(array $defeatedDemons): int
{
    global $turnsNumber, $initialStamina, $maxStamina;
    $hero = new Hero($maxStamina, $initialStamina);
    $dIdx = 0;
    for ($t = 0; $t < $turnsNumber; $t++) {
        //echo "\nT = $t";
        /** @var Demon $d */
        $d = $defeatedDemons[$dIdx];
        // Stamina recover
        //echo "\nStamina = {$hero->stamina}";
        $hero->recoverStamina($t);
        //echo "\nNew stamina = {$hero->stamina}";
        // Battle demon
        if($hero->canDefeat($d)) {
            $hero->battleDemon($d, $t);
            $dIdx++;
            //echo "\nDefeated = {$d->id}";
            //echo "\nStamina after battle = {$hero->stamina}";
        }
    }
    return $hero->fragments;
}

function output(array $defeatedDemons, int $score) {
    global $fileManager, $fileName;
    $output = '';
    foreach ($defeatedDemons as $d) {
        $output .= $d->id . "\n";
    }
    Log::out("SCORE($fileName) = " . $score);
    $fileManager->output($output, 'score_' . $score);
}

/* Algo */
$currentStamina = $initialStamina;
$currentScore = 0;
$t = 0;

$bestScore = 0;
$bestSolution = [];
for($i = 0; $i < 1000000; $i++) {
    $defeatedDemons = $demons;
    shuffle($defeatedDemons);
    $currentScore = calculateScoreForSolution($defeatedDemons);
    if($currentScore > $bestScore) {
        $bestScore = $currentScore;
        $bestSolution = $defeatedDemons;
        echo "Best score = $bestScore\n";
        output($bestSolution, $bestScore);
    }
    if($i % 1000 === 0) {
        echo "Tentativo $i\n";
    }
}

/*
for ($t = 0; $t < $turnsNumber; $t++) {
    // Do something
}
*/

/*
foreach ($demons as $d) {
    echo $d->id . ' => ' . $d->getScoreAtTime($t) . "\n";
}
*/




