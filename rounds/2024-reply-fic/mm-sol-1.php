<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use JMGQ\AStar\AStar;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/DomainLogic.php';

global $fileName;
/** @var FileManager */
global $fileManager;

/* Config & Pre runtime */
$fileName = 'a';
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
/** @var GoldenPoint $goldenPoints */
/** @var SilverPoint $silverPoints */
/** @var TileType $tileTypes */
/** @var MapManager $mapManager */


/* Algo */

$domainLogic = new DomainLogic($mapManager);
$aStar = new AStar($domainLogic);
$solution = $aStar->run([0,0], [6,6]);

print_r($solution);


// RUN
$SCORE = 0;
Log::out("Run started...");

#$fileManager->outputV2(getOutput(), $SCORE);
