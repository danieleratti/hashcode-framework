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
$fileName = 'e';
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
        $avgTileCost = 40;
        $cellAdjacent = $this->mapManager->map[$adjacent[0]][$adjacent[1]];
        if($cellAdjacent instanceof SilverPoint) {
            #return $avgTileCost - $adjacent->score;
            return 0;
        } else {
            #return $avgTileCost;
            return 10;
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


/* Vars */
/** @var GoldenPoint $goldenPoints */
/** @var SilverPoint $silverPoints */
/** @var TileType $tileTypes */
/** @var MapManager $mapManager */


/* Algo */

$domainLogic = new DomainLogic($mapManager);
$aStar = new AStar($domainLogic);
#$solution = $aStar->run([390,145], [192,682]);
$solution = $aStar->run([390,145], [327,205]);
#$solution = $aStar->run([1,44], [150,50]);
#$solution = $aStar->run([390,145], [328,442]);

print_r($solution);

$score = 0;
$visualStandard = new VisualStandard(700, 700);
foreach($solution as $c) {
    $visualStandard->setPixel($c[0], $c[1], Colors::purple9);
    $score -= 40;
    if($mapManager->map[$c[0]][$c[1]] instanceof SilverPoint) {
        $score += 300;
    }
}
$visualStandard->save('test-e');
Log::out("Score: $score");

// RUN
$SCORE = 0;
Log::out("Run started...");

#$fileManager->outputV2(getOutput(), $SCORE);
