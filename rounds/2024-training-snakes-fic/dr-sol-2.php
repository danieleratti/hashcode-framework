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
/** @var Contributor[] */
global $contributors;
/** @var Project[] */
global $projects;

/* Config & Pre runtime */
$fileName = 'g';
#$param1 = 1;
#Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'reader.php';

/* Functions */
function getOutput()
{
    global $snakes;
    ArrayUtils::array_keysort_objects($snakes, 'id', SORT_ASC);
    $output = [];
    foreach($snakes as $snake) {
        $output[] = $snake->getOutputPath();
    }
    return implode("\n", $output);
}

function positionNewSnake(Snake $snake) {
    global $map, $vSpan, $headR, $headC, $bottomR, $rowsCount, $columnsCount, $SCORE, $mapManager, $dir;
    $positioned = false;
    while($snake->getRemainingLength() > 0) {
        if(($dir == 1 && $headC == $columnsCount-2) || $dir == -1 && $headC == 1) {
            $dir *= -1;
            $headR++;
            if($map[$headR][$headC] > 0)
                $SCORE += $map[$headR][$headC];
            if($positioned)
                $snake->addDirectionCommand("D", true);
            else
                $snake->setInitialHead($headR, $headC);
        } else {
            $headC += $dir;
            if($map[$headR][$headC] > 0)
                $SCORE += $map[$headR][$headC];
            if($dir == 1) {
                if($positioned)
                    $snake->addDirectionCommand("R", true);
                else
                    $snake->setInitialHead($headR, $headC);
            }
            elseif($dir == -1) {
                if($positioned)
                    $snake->addDirectionCommand("L", true);
                else
                    $snake->setInitialHead($headR, $headC);
            }
        }
        $positioned = true;
    }
}

/* Vars */
/** @var Snake[] $snakes */
/** @var int $rowsCount */
/** @var int $columnsCount */
/** @var int $snakesCount */

// Config
$vSpan = 5;

// Run
$SCORE = 0;
$headR = 0;
$headC = 0;
$bottomR = $vSpan-1;
$dir = 1;

ArrayUtils::array_keysort_objects($snakes, 'length', SORT_DESC);

$snakeIteration = 0;
foreach($snakes as $snake) {
    $snakeIteration++;
    // nuovo snake
    Log::out("Snake ".$snake->id." (iteration ".$snakeIteration."/".count($snakes).") // Head: R=$headR C=$headC");
    positionNewSnake($snake);
    Log::out("SCORE = " . $SCORE ." // Head: R=$headR C=$headC");
}

//$snakes[1]->setInitialHead(0, 0);
//$snakes[1]->addDirectionCommand("L");

/* Runtime */
#ArrayUtils::array_keysort_objects($remainingProjects, 'score', SORT_DESC);
$fileManager->outputV2(getOutput(), $SCORE);

//Log::out("Uploading!", 0, "green");
