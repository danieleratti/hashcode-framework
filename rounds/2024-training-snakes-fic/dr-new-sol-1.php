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
/** @var Array[int][int] */
global $map;

/* Config & Pre runtime */
$fileName = 'a';
#$param1 = 1;
#Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'reader.php';

/* Classes */
class MatrixCreator {
    private static $cache = [];

    public static function getMask($maxWidth, $maxHeight, $startingRow) {

        if(@self::$cache[$maxWidth][$maxHeight][$startingRow])
            return self::$cache[$maxWidth][$maxHeight][$startingRow];

        // Initialize the matrix, visited matrix, and allPaths array.
        $matrix = array_fill(0, $maxHeight, array_fill(0, $maxWidth, 0));
        $visited = array_fill(0, $maxHeight, array_fill(0, $maxWidth, false));
        $allPaths = [];

        // Start the snake from position (0,0).
        self::moveSnakeForMatrix($startingRow, 0, [], $allPaths, $matrix, $visited);

        self::$cache[$maxWidth][$maxHeight][$startingRow] = $allPaths;

        return $allPaths;
    }

    private static function moveSnakeForMatrix($x, $y, $path, &$allPaths, $matrix, $visited) {
        $rowCount = count($matrix);
        $colCount = count($matrix[0]);

        // Check if current position is out of bounds or already visited.
        if ($x < 0 || $x >= $rowCount || $y < 0 || $y >= $colCount || $visited[$x][$y]) {
            return;
        }

        // Add current cell to path and mark it as visited.
        $newPath = array_merge($path, [[$x, $y]]);
        $visited[$x][$y] = true;

        // If we reached the last column, add the path to allPaths and return.
        if ($y == $colCount - 1) {
            $allPaths[] = $newPath;
            // Do not return here if you want to explore all possible lengths.
        }

        // Move to adjacent cells (right, up, down)
        self::moveSnakeForMatrix($x, $y + 1, $newPath, $allPaths, $matrix, $visited); // Move right
        self::moveSnakeForMatrix($x - 1, $y, $newPath, $allPaths, $matrix, $visited); // Move up
        self::moveSnakeForMatrix($x + 1, $y, $newPath, $allPaths, $matrix, $visited); // Move down

        // Backtrack: unmark the current cell as visited for other paths.
        $visited[$x][$y] = false;
    }
}

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


/* Vars */
/** @var Snake[] $snakes */
/** @var int $rowsCount */
/** @var int $columnsCount */
/** @var int $snakesCount */


// RUN
$SCORE = 0;

// Creating the masks
ArrayUtils::array_keysort_objects($snakes, 'length', SORT_DESC);

$posR = 0;
$posC = 0;
foreach($snakes as $snake) {

}


#$fileManager->outputV2(getOutput(), $SCORE);
