<?php

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
