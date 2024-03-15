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
$fileName = 'f';
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
    global $map, $vSpan, $headR, $headC, $bottomR, $rowsCount, $columnsCount, $SCORE, $mapManager;
    $headC++;
    if ($headC == $columnsCount) { // superata la fine
        $headC = 0;
        $bottomR += $vSpan;
        $headR = $bottomR - 1;
    }
    while($mapManager->map[$headR][$headC] == ".") {
        $headR++;
    }
    if($map[$headR][$headC] > 0)
        $SCORE += $map[$headR][$headC];
    $snake->setInitialHead($headR, $headC);
}

function bestMoveSnake(Snake $snake) {
    global $map, $vSpan, $headR, $headC, $bottomR, $rowsCount, $columnsCount, $SCORE, $mapManager;

    if($mapManager->map[$headR][$headC+1] == ".") {
        $headR++;
        if($map[$headR][$headC] > 0)
            $SCORE += $map[$headR][$headC];
        $snake->addDirectionCommand("D", true);
    }
    else
    {
        $headC++;
        if($map[$headR][$headC] > 0)
            $SCORE += $map[$headR][$headC];
        $snake->addDirectionCommand("R", true);
    }
    if ($headC == $columnsCount) {
        $headC = 0;
        if ($snake->getRemainingLength() > 0) {
            $headR++;
            if ($map[$headR][$headC] > 0)
                $SCORE += $map[$headR][$headC];
            $snake->addDirectionCommand("D", true);
        }
    }

    /*
    if($snake->getRemainingLength() > 0) {
        $headR++;
        if ($map[$headR][$headC] > 0)
            $SCORE += $map[$headR][$headC];
        $snake->addDirectionCommand("D", true);
    }*/

    if($snake->getRemainingLength() > 0) {
        if ($headC == $columnsCount - 1) { // raggiunta l'ultima colonna
            $bottomR += $vSpan;
            for($i=0;$i<min($snake->getRemainingLength(), $bottomR-$vSpan+1-$headR+1);$i++) { // vado in giù per andare all'inizio dei nuovi vertical bounds
                $headR++;
                if($map[$headR][$headC] > 0)
                    $SCORE += $map[$headR][$headC];
                $snake->addDirectionCommand("D", true);
            }
        }
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
$headC = -1;
$bottomR = $vSpan-1;

ArrayUtils::array_keysort_objects($snakes, 'length', SORT_DESC);

$snakeIteration = 0;
foreach($snakes as $snake) {
    $snakeIteration++;
    // nuovo snake
    Log::out("Snake ".$snake->id." (iteration ".$snakeIteration."/".count($snakes).") // Head: R=$headR C=$headC");
    positionNewSnake($snake);
    while($snake->getRemainingLength() > 0) {
        bestMoveSnake($snake);
    }
    Log::out("SCORE = " . $SCORE ." // Head: R=$headR C=$headC");
}

//$snakes[1]->setInitialHead(0, 0);
//$snakes[1]->addDirectionCommand("L");

/* Runtime */
#ArrayUtils::array_keysort_objects($remainingProjects, 'score', SORT_DESC);
$fileManager->outputV2(getOutput(), $SCORE);

//Log::out("Uploading!", 0, "green");
