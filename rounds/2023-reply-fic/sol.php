<?php

use Utils\FileManager;
use Utils\Log;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

$fileName = 'a';

/* Reader */
include_once 'reader.php';

global $snakes;
global $map;
global $mapManager;
global $rowsCount;
global $columnsCount;
global $snakesCount;

/* Functions */


/* Algo */

$cells = [];
for ($r = 0; $r < $rowsCount; $r++) {
    foreach ($mapManager->map[$r] as $c => $v) {
        if ($v !== '*') {
            $cells["$r,$c"] = $v;
        }
    }
}
arsort($cells);

$snakeId = 0;
foreach ($cells as $k => $v) {
    [$r, $c] = explode(',', $k);
    $r = (int)$r;
    $c = (int)$c;
    $snake = $snakes[$snakeId];
    if (!$snake) break;
    $snake->setInitialHead($r, $c);
    while ($snake->getRemainingLength() > 0) {
        $prevCommand = $snake->commands[count($snake->commands) - 1];
        $prevDir = is_string($prevCommand) ? $prevCommand : 'D';
        $dir = null;
        while (true) {
            $dir = getNextDir($prevDir);
            // Controllo se posso
            if (!$mapManager->hasSnakeIf($dir, $snake->head)) {
                break;
            }
            $prevDir = $dir;
        }
        $snake->addDirectionCommand($dir, true);
        unset($cells[$snake->head[0] . ',' . $snake->head[1]]);
    }
    $snakeId++;
}

function getNextDir(string $prevDir): string
{
    return match ($prevDir) {
        'U' => 'R',
        'R' => 'D',
        'D' => 'L',
        'L' => 'U'
    };
}


print_r($cells);


Log::out('Finito');
