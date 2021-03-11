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
include 'dr-reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Foo[] $FOO */
/** @var int $DURATION */

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
