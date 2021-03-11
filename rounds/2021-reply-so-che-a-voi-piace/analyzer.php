<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Graph;

$fileName = 'a';

include 'reader.php';

/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antenna */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$analyzer = new Analyzer($fileName, [
    'duration' => $DURATION,
    'intersectionsCount' => $N_INTERSECTIONS,
    'streetsCount' => $N_STREETS,
    'carsCount' => $N_CARS,
    'bonus' => $BONUS,
]);
$analyzer->addDataset('streets', $STREETS, ['duration', 'nSemaphorePassingCars']);
$analyzer->addDataset('intersections', $INTERSECTIONS, ['streetsIn', 'streetsOut', 'nSemaphorePassingCars']);
$analyzer->addDataset('cars', $CARS, ['streets', 'pathDuration']);
$analyzer->analyze();

/*
$graph = new Graph($fileName);

$vertexes = collect($INTERSECTIONS)->map(function (Intersection $i) { return ['id' => $i->id, 'color' => 'red', 'shape' => 'box']; })->toArray();
$edges = array_values(collect($STREETS)->map(function (Street $s) { return ['from' => $s->start->id, 'to' => $s->end->id]; })->toArray());

$graph->plotGraph($vertexes, $edges);
*/
