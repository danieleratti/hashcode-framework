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

$fileName = 'a';

/* Reader */
include_once 'mm-reader.php';
//print_r($demons);

/* Functions */

function calculateScoreForSolution(array $defeatedDemons): int
{
    global $turnsNumber, $initialStamina, $staminaRecovering;
    $currentStamina = $initialStamina;
    $currentScore = 0;
    $dIdx = 0;
    for ($t = 0; $t < $turnsNumber; $t++) {
        /** @var Demon $d */
        $d = $defeatedDemons[$dIdx];
        // Stamina recover
        if(isset($staminaRecovering[$t])) {
            $currentStamina += $staminaRecovering[$t];
        }
        // Battle demon
        if($d->staminaRequired <= $currentStamina) {
            battleDemon($d, $t);
        }
    }
    return $currentScore;
}

function battleDemon(Demon $d, int $t)
{
    global $currentStamina, $currentScore, $staminaRecovering;
    $currentStamina -= $d->staminaRequired;
    $currentScore += $d->getScoreAtTime($t);
    $staminaRecovering[$t + $d->staminaRecoverTime] = $d->staminaRecoverAmount;
}

/* Algo */
$currentStamina = $initialStamina;
$currentScore = 0;
$t = 0;

$defeatedDemons = $demons;
shuffle($defeatedDemons);

$currentScore = calculateScoreForSolution($defeatedDemons);

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

/* SCORING & OUTPUT */
$output = '';
foreach ($defeatedDemons as $d) {
    $output .= $d->id . "\n";
}
Log::out("SCORE($fileName) = " . $currentScore);
$fileManager->output($output, 'score_' . $currentScore);
