<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;

$fileName = 'f';

include 'dr-reader.php';

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
$analyzer->addDataset('streets', $STREETS, ['duration']);
$analyzer->addDataset('intersections', $INTERSECTIONS, ['streetsIn', 'streetsOut']);
$analyzer->addDataset('cars', $CARS, ['streets']);
$analyzer->analyze();

