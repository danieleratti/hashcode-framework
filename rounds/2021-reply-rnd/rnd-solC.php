<?php

use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'c';

include 'rnd-reader.php';

/** @var Collection|Building[] $BUILDINGS*/
/** @var Collection|Antenna[] $ANTENNAS*/
/** @var MAP $MAP */
/** @var FileManager $fileManager */


$test='123';
$sortedBuildings = $BUILDINGS->sortByDesc('connectionSpeedWeight');
$sortedAntennas = $ANTENNAS->sortByDesc('connectionSpeed');

foreach ($sortedAntennas as $k => $antenna){
    $bestBuilding= $sortedBuildings->shift()->cell;
    $sortedAntennas[$k]->cell= $bestBuilding;
}



Log::out('Output...');
$assignedAntennas = $sortedAntennas->filter(function($item){
    return $item->cell!==null;
});
$output = $assignedAntennas->count() . PHP_EOL;
/** @var Antenna $antenna */
foreach ($assignedAntennas as $k => $antenna) {
    $id= $antenna->id;
    $y = $antenna->cell->y;
    $x = $antenna->cell->x;
    $output .= $id .' '. $x . ' ' . $y . PHP_EOL;
}

$fileManager->outputV2($output, time());
