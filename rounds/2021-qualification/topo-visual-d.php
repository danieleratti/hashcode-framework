<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Graph;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'a';
$bestCarsPerc = 1.0;
$cycleMaxDuration = 5;
$OVERHEADQUEUE = 0;
Cerberus::runClient(['fileName' => 'd', 'bestCarsPerc' => 1.0, 'cycleMaxDuration' => 5]);
Autoupload::init();
include 'topo-reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Car[] $CARS */
/** @var Collection|Street[] $STREETS */
/** @var Collection|Intersection[] $INTERSECTIONS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

/* COLLECTIONS */
$CARS = collect($CARS);
$CARS->keyBy('id');

$STREETS = collect($STREETS);
$STREETS->keyBy('name');

$INTERSECTIONS = collect($INTERSECTIONS);
$INTERSECTIONS->keyBy('id');

/* VISUAL */
$STREETS = $STREETS->where('nSemaphorePassingCars', '>', 0);
echo count($STREETS);
die();

$graph = new Graph('D');

$vertexes = [['id' => 'a'], ['id' => 'b'], ['id' => 'c', 'label' => 'CCC', 'color' => 'red', 'shape' => 'box']];
$edges = [['from' => 'a', 'to' => 'b'], ['from' => 'b', 'to' => 'c', 'color' => 'blue']];

$graph->plotGraph($vertexes, $edges);


