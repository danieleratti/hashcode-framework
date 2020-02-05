<?php

/**
 * @var integer $gridRows
 * @var integer $gridCols
 * @var integer $routerRange
 * @var integer $backboneCosts
 * @var integer $routerCosts
 * @var integer $budget
 * @var integer $backboneRow
 * @var integer $backboneCol
 * @var integer[][] $gridArray
 */

use Utils\FileManager;

require_once '../../bootstrap.php';

$fileManager = new FileManager($fileName);
$contentRows = explode("\n", $fileManager->get());
list($gridRows, $gridCols, $routerRange) = explode(' ', $contentRows[0]);
list($backboneCosts, $routerCosts, $budget) = explode(' ', $contentRows[1]);
list($backboneRow, $backboneCol) = explode(' ', $contentRows[2]);

$gridArray = [];
foreach (array_slice($contentRows, 3, count($contentRows)) as $stringRow) {
    $gridArray[] = str_split($stringRow);
}

function isThereAWall($gridArray, $r1, $c1, $r2, $c2)
{
    for ($i = min($r1, $r2); $i < max($r1, $r2); $i++) {
        for ($j = min($c1, $c2); $j < max($c1, $c2); $j++) {
            if ($gridArray[$i][$j] == '#') {
                return true;
            }
        }
    }
    return false;
}

function addWifi($boolGrid, $gridArray, $r, $c, $wifiRange)
{
    for ($i = max(0, $r - $wifiRange); $i < min(count($gridArray), $r + $wifiRange); $i++) {
        for ($j = max(0, $c - $wifiRange); $j < min(count($gridArray[$i]), $c + $wifiRange); $j++) {
            $boolGrid[$i][$j] = !isThereAWall($gridArray, $i, $j, $r, $c);
        }
    }
    return $boolGrid;
}
