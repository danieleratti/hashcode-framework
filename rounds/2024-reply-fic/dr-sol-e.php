<?php

use JMGQ\AStar\Example\Terrain\Position;
use JMGQ\AStar\Example\Terrain\SequencePrinter;
use JMGQ\AStar\Example\Terrain\TerrainCost;
use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use JMGQ\AStar\AStar;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;

/* Config & Pre runtime */
$fileName = 'e';
$param1 = 1;
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
/** @var MapManager $mapManager */


// RUN
$SCORE = 0;
Log::out("Run started...");


$columnScores = [];
for($c=0;$c<count($mapManager->map[0]);$c++) {
    $score = 0;
    for($r=0;$r<count($mapManager->map);$r++) {
        $cell = $mapManager->map[$r][$c];
        if($cell instanceof SilverPoint) {
            $score += $cell->score;
        }
    }
    $columnScores[$c] = $score;
}

arsort($columnScores);
print_r($columnScores);
/*
$terrainCost = new TerrainCost([
    [3, 2, 3, 6, 1],
    [1, 3, 4, 1, 1],
    [3, 1, 1, 4, 1],
    [1, 1, 5, 2, 1]
]);

$start = new Position(0, 0);
$goal = new Position(0, 4);

$domainLogic = new DomainLogic($terrainCost);
$aStar = new AStar($domainLogic);

$solution = $aStar->run($start, $goal);

$printer = new SequencePrinter($terrainCost, $solution);

$printer->printSequence();
*/


#$fileManager->outputV2(getOutput(), $SCORE);
