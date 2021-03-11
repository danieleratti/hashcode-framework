<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Graph;

$fileName = 'f';

include 'reader.php';

/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antennas */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$analyzer = new Analyzer($fileName, [
    'H' => $H,
    'W' => $W,
    'buildingCount' => $totalBuildings,
    'antennasCount' => $totalAntennas,
    'lastBonus' => $finalReward,
]);

$analyzer->addDataset('buildings', $buildings, ['latencyWeight', 'speedWeight']);
$analyzer->addDataset('antennas', $antennas, ['range', 'speed']);
$analyzer->analyze();

/*
$graph = new Graph($fileName);

$vertexes = collect($INTERSECTIONS)->map(function (Intersection $i) { return ['id' => $i->id, 'color' => 'red', 'shape' => 'box']; })->toArray();
$edges = array_values(collect($STREETS)->map(function (Street $s) { return ['from' => $s->start->id, 'to' => $s->end->id]; })->toArray());

$graph->plotGraph($vertexes, $edges);
*/
