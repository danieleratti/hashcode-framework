<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use JMGQ\AStar\AStar;
use JMGQ\AStar\DomainLogicInterface;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;

/* Config & Pre runtime */
$fileName = 'c';
$_visualyze = false;
$_analyze = false;
//$param1 = 1;
//Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'reader.php';

/* Classes */
class DomainLogic implements DomainLogicInterface
{
    public MapManager $mapManager;

    public function __construct(MapManager $mapManager)
    {
        $this->mapManager = $mapManager;
    }

    public function getAdjacentNodes(mixed $node): iterable
    {
        [$r, $c] = $node;
        $adjacentNodes = [];
        if ($r > 0) {
            $adjacentNodes[] = [$r - 1, $c];
        }
        if ($r < $this->mapManager->rowsCount - 1) {
            $adjacentNodes[] = [$r + 1, $c];
        }
        if ($c > 0) {
            $adjacentNodes[] = [$r, $c - 1];
        }
        if ($c < $this->mapManager->columnsCount - 1) {
            $adjacentNodes[] = [$r, $c + 1];
        }

        return $adjacentNodes;
    }

    public function calculateRealCost(mixed $node, mixed $adjacent): float|int
    {
        global $pointPassed;
        $avgTileCost = 40;

        if(@$pointPassed[$adjacent[0]][$adjacent[1]])
            return 1000;

        $cellAdjacent = $this->mapManager->map[$adjacent[0]][$adjacent[1]];
        if($cellAdjacent instanceof SilverPoint) {
            #return $avgTileCost - $adjacent->score;
            return 0;
        } else {
            #return $avgTileCost;
            return 40;
        }
    }

    public function calculateEstimatedCost(mixed $fromNode, mixed $toNode): float|int
    {
        [$r1, $c1] = $fromNode;
        [$r2, $c2] = $toNode;
        return abs($r1 - $r2) + abs($c1 - $c2);
    }

    // ...
}

/* Functions */
function getOutput()
{
    global $snakes;
    ArrayUtils::array_keysort_objects($snakes, 'id', SORT_ASC);
    $output = [];
    return implode("\n", $output);
}

function getTile($prevCell, $currentCell, $nextCell) {
    // Determine the direction from previous to current cell
    $prevDirection = "";
    if ($prevCell[0] == $currentCell[0]) {
        // Movement in the horizontal direction
        $prevDirection = ($prevCell[1] < $currentCell[1]) ? "left" : "right";
    } else {
        // Movement in the vertical direction
        $prevDirection = ($prevCell[0] < $currentCell[0]) ? "up" : "down";
    }

    // Determine the direction from current to next cell
    $nextDirection = "";
    if ($nextCell[0] == $currentCell[0]) {
        // Movement in the horizontal direction
        $nextDirection = ($nextCell[1] > $currentCell[1]) ? "right" : "left";
    } else {
        // Movement in the vertical direction
        $nextDirection = ($nextCell[0] > $currentCell[0]) ? "down" : "up";
    }

    // Match the direction pair to the correct tile
    switch ($prevDirection . '-' . $nextDirection) {
        case 'left-right':
        case 'right-left':
            return '3';
        case 'up-down':
        case 'down-up':
            return 'C';
        case 'down-right':
        case 'right-down':
            return '5';
        case 'left-down':
        case 'down-left':
            return '6';
        case 'up-right':
        case 'right-up':
            return '9';
        case 'left-up':
        case 'up-left':
            return 'A';
        default:
            return 'Error'; // Or handle unexpected cases differently
    }
}



/* Vars */
/** @var GoldenPoint $goldenPoints */
/** @var SilverPoint $silverPoints */
/** @var TileType $tileTypes */
/** @var MapManager $mapManager */


/* Algo */

$domainLogic = new DomainLogic($mapManager);
$aStar = new AStar($domainLogic);
#$solution = $aStar->run([390,145], [192,682]);
#$solution = $aStar->run([390,145], [327,205]);
#$solution = $aStar->run([1,44], [150,50]);
#$solution = $aStar->run([17,70], [3,76]);

$points = [];
$areaSize = 5;
$virtualArea = [];
foreach($mapManager->map as $row => $rr) {
    foreach($rr as $col => $c) {
        if($c instanceof SilverPoint) {
            $points[$row][$col] = true;
            if( !$virtualArea[floor($row/$areaSize)][floor($col/$areaSize)] || $virtualArea[floor($row/$areaSize)][floor($col/$areaSize)]['score'] < $c->score)
                $virtualArea[floor($row/$areaSize)][floor($col/$areaSize)] = ['score' => $c->score, 'rc' => [$row, $col]];
        }
    }
}


$virtualAreaKeypoints = [];

$dirR = -1;
for($i=6*2;$i<10*2;$i++)
    $virtualAreaKeypoints[] = [$i, 3*2];
$r = 9*2;
$c = 4*2;
for(;$c<14*2;$c++) {
    if($c == 14*2 && $r == 4*2)
        break;
    for(;$r>=0&&$r<10*2;$r+=$dirR) {
        $virtualAreaKeypoints[] = [$r, $c];
    }
    $r -= $dirR;
    $dirR *= -1;
}


$keyPoints = [];
$keyPoints[] = [55,31];

/*
$keyPoints[] = [58,13];
$keyPoints[] = [63,9];
$keyPoints[] = [87,7];
$keyPoints[] = [91,16];
$keyPoints[] = [70,26];
*/

foreach($virtualAreaKeypoints as $k => $vp)
    if($virtualArea[$vp[0]][$vp[1]])
        $keyPoints[] = $virtualArea[$vp[0]][$vp[1]]['rc'];

$keyPoints[] = [44,135];

print_r($keyPoints);
#die();
#$keyPoints = [[55,31], [91,3], [11,10], [44,135]];

$tile2cost = [];
$tile2count = [];
foreach($tileTypes as $tile) {
    $tile2cost[$tile->id] = $tile->cost;
    $tile2count[$tile->id] = $tile->count;
}

$solution = [];
$pointPassed = [];
foreach($keyPoints as $k => $point)
    if($k) {
        echo "Executing keypoint ".$point[0]." - ".$point[1]." (".($k+1)."/".count($keyPoints).")\n";
        $prevPoint = $keyPoints[$k - 1];
        $_solution = $aStar->run($prevPoint, $point);
        #$solution = array_merge($solution, $_solution);
        foreach($_solution as $k2 => $c) if($k2 || count($solution) == 0) {
            $pointPassed[$c[0]][$c[1]] = true;
            $solution[] = $c;
        }
    }

#print_r($solution);

$tiles = [];
$score = 0;
$visualStandard = new VisualStandard(count($mapManager->map), count($mapManager->map[0]));
foreach($solution as $k => $c) {
    if($k && $k < count($solution)-1) { # non primo e non ultimo
        $prevC = $solution[$k-1];
        $nextC = $solution[$k+1];
        $tileId = getTile($prevC, $c, $nextC);
        $score -= $tile2cost[$tileId];
        $tile2count[$tileId]--;
        if($tile2count[$tileId] < 0) {
            Log::out("Abbiamo finito le tile $tileId");
        }
        else
            $visualStandard->setPixel($c[0], $c[1], Colors::purple9);

        if ($mapManager->map[$c[0]][$c[1]] instanceof SilverPoint) {
            $score += $mapManager->map[$c[0]][$c[1]]->score;
        }
        #$prevDelta = [ $c[0] - $prevC[0], $c[1] - $prevC[1] ];
        #$nextDelta = [ $c[0] - $nextC[0], $c[1] - $nextC[1] ];
        $tiles[] = $tileId." ".$c[1]." ".$c[0];
    }
}
$visualStandard->save('griglia');
Log::out("Score: $score");

$fileManager->outputV2(implode("\n", $tiles), $score);

// RUN
$SCORE = 0;
Log::out("Run finished...");

#$fileManager->outputV2(getOutput(), $SCORE);
