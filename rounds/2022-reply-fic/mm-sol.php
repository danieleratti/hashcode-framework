<?php

/** @var string */
global $fileName;

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

$fileName = 'a';

/* Reader */
include_once 'mm-reader.php';
print_r($demons);

/* Functions */

function battleDemon(Demon $d) {
    global $t, $currentStamina, $currentScore;
    $currentStamina -= $d->staminaRequired;
    $currentScore += $d->getScoreAtTime($t);
}

/* Algo */
$currentStamina = $initialStamina;
$currentScore = 0;
$t = 0;

/*for ($t = 0; $t < $turnsNumber; $t++) {
    // Do something
}*/

foreach ($demons as $d) {
    echo $d->id . ' => ' . $d->getScoreAtTime($t) . "\n";
}
