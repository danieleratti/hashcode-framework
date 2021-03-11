<?php

use Utils\FileManager;

$fileName = 'c';

include 'reader.php';

/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Developer[] $DEVELOPERS */
/** @var FileManager $fileManager */
/** @var Company[] $COMPANIES */
/** @var Map $MAP */

$visitedCells = [];

for ($y = 0; $y < $HEIGHT; $y++) {
    for ($x = 0; $x < $WIDTH; $x++) {
        $cell = $MAP->map[$y][$x];

        if ($visitedCells[$cell->y][$cell->x]) {
            continue;
        }
        if($cell->type == '#') {
            $visitedCells[$cell->y][$cell->x] = true;
            continue;
        }

        //$near = $MAP->getFreeNeighbours()

    }
}
