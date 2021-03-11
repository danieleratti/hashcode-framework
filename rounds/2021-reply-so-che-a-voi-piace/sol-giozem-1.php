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
/** @var Antenna[] $antennas */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$SCORE = 0;
$placedAntennas = 0;

/* FUNCTIONS */

/* ALGO */


/* SCORING & OUTPUT */
$output = $placedAntennas . PHP_EOL;
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $output .= $antenna->id . " " . $antenna->r . " " . $antenna->c . PHP_EOL;
    }
}

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'score_' . $SCORE);
