<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Graph;

$fileName = 'c';

include 'topo-reader.php';

/** @var Collection|Street[] $STREETS */
/** @var Collection|Intersection[] $INTERSECTIONS */
/** @var Collection|Car[] $CARS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

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
