<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;

/* Config & Pre runtime */
$fileName = 'f';
$_visualyze = false;
$_analyze = false;
//$param1 = 1;
//Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'reader.php';

/* Classes */

/* Functions */
function getOutput()
{
    global $snakes;
    ArrayUtils::array_keysort_objects($snakes, 'id', SORT_ASC);
    $output = [];
    return implode("\n", $output);
}


/* Vars */
/** @var Snake[] $snakes */
/** @var int $snakesCount */


// RUN
$SCORE = 0;
Log::out("Run started...");

#$fileManager->outputV2(getOutput(), $SCORE);
