<?php

use Utils\ArrayUtils;
use Utils\FileManager;
use Utils\Log;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

$fileName = 'f';

/* Reader */
include_once 'reader.php';

global $snakes;
global $map;
global $mapManager;
global $rowsCount;
global $columnsCount;
global $snakesCount;

// Defines
$snakePerc = 1.0;

// Placement - Calculations
$snakeSum = array_reduce($snakes, fn($carry, Snake $snake) => $carry + $snake->length, 0);
$initalAllocSnake = floor($snakeSum * $snakePerc);
$vOffset = $rowsCount * $columnsCount / $initalAllocSnake;
$bsRowUsage = $columnsCount + $vOffset;
$stepLength = $bsRowUsage / $vOffset;

// Placement - Bigsnake
$bigsnake = [];
$r = 0;
$c = 0;
$currStep = $stepLength;
for ($remainingSnake = $initalAllocSnake; $remainingSnake > 0; $remainingSnake--) {
    $currStep--;
    $mapManager->putSnake($r, $c, MapManager::BIGSNAKE_LAYER);
    $bigsnake[] = [$r, $c];
    if ($currStep < 0) {
        $currStep += $stepLength;
        $r = ($r + 1) % $rowsCount;
    } else {
        $c = ($c + 1) % $columnsCount;
    }
}

$mapManager->visualizeWithSnakes("$fileName-bisgnake", MapManager::BIGSNAKE_LAYER);

//echo $snakeSum . '/' . $columnsCount . '/' . $rowsCount . PHP_EOL;
//echo $vOffset . ' --- ' . $bsRowUsage . ' --- ' . $stepLength . PHP_EOL;

// Snakes cutting
try {
    $cursor = 0;
    foreach ($snakes as $snake) {
        $snake->setInitialHead(...$bigsnake[$cursor]);
        $cursor++;
        for ($i = 1; $i < $snake->length; $i++) {
            $direction = getDirection($bigsnake[$cursor - 1], $bigsnake[$cursor]);
            $snake->addDirectionCommand($direction);
            $cursor++;
        }
        if ($cursor > count($bigsnake)) {
            throw new Error('Too many snakes?');
        }
    }
} catch (Error $e) {
    $mapManager->visualizeWithSnakes("$fileName-error");
    throw $e;
}

$mapManager->visualizeWithSnakes("$fileName-snakes");

function getDirection($from, $to)
{
    if ($from[0] === $to[0]) {
        if ($from[1] === $to[1] - 1) {
            return 'R';
        } elseif ($from[1] === $to[1] + 1) {
            return 'L';
        } elseif ($from[1] < $to[1]) {
            return 'L';
        } else {
            return 'R';
        }
    } else {
        if ($from[0] === $to[0] - 1) {
            return 'D';
        } elseif ($from[0] === $to[0] + 1) {
            return 'U';
        } elseif ($from[0] < $to[0]) {
            return 'U';
        } else {
            return 'D';
        }
    }
}

function getOutput()
{
    global $snakes;
    ArrayUtils::array_keysort_objects($snakes, 'id', SORT_ASC);
    $output = [];
    foreach ($snakes as $snake) {
        $output[] = $snake->getOutputPath();
    }
    return implode("\n", $output);
}

$fileManager->outputV2(getOutput(), 0);

Log::out('Finito');