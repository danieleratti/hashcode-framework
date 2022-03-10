<?php

use Utils\ArrayUtils;
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
    $defeatedDemons = array_values($defeatedDemons);
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

// Filter out unuseful demons
$demons = array_filter($demons, fn(Demon $d) => $d->totalFragments > 0);
$demons = array_filter($demons, fn(Demon $d) => $d->staminaRecoverTime < 1000);

foreach ($demons as $d) {
    /** @var Demon $d */
    $d->calculateTotalFragments();
    $d->calculateValue();
}
ArrayUtils::array_keysort_objects($demons, 'value');

$defeatedDemons = [];
$hero = new Hero($maxStamina, $initialStamina);
for ($t = 0; $t < $turnsNumber; $t++) {
    $hero->recoverStamina($t);
    foreach ($demons as $d) {
        /** @var Demon $d */
        if($hero->canDefeat($d)) {
            $hero->battleDemon($d, $t);
            $defeatedDemons[] = $d;
            break;
        }
    }
}


$defeatedDemons = $demons;
ArrayUtils::array_keysort_objects($defeatedDemons, 'value');
$currentScore = calculateScoreForSolution($defeatedDemons);
echo "Best score = $currentScore\n";
output($defeatedDemons, $currentScore);

