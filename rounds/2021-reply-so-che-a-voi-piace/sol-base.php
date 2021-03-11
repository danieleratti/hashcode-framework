<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);
Autoupload::init();
include 'reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antenna */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

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
