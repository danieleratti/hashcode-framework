<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

/* CONFIG */
$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);
Autoupload::init();
include __DIR__ . '/dr-reader.php';

/* VARIABLES */
/** @var int $W */
/** @var int $H */
/** @var int $buildingsCount */
/** @var int $antennasCount */
/** @var int $reward */
/** @var Building[] $BUILDINGS */
/** @var Antenna[] $ANTENNAS */

$SCORE = 0;

/* FUNCTIONS */
/**
 * @param $semaphores
 * @return string
 */
function getOutput($semaphores)
{
    $output = [];
    $output[] = count($semaphores);
    $output = implode("\n", $output);
    return $output;
}

/* ALGO */


/* SCORING & OUTPUT */
Log::out("SCORE($fileName) = ");
//$fileManager->outputV2(getOutput([]), 'score_' . $SCORE);
