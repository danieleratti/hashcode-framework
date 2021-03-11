<?php

use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include 'rnd-reader.php';

/** @var Collection $BUILDINGS */
/** @var Collection $ANTENNAS */
/** @var Map $MAP */


$ellipseSize = 1;

$visualCity = new VisualStandard($MAP->height, $MAP->width);
foreach($BUILDINGS as $building) {
    $buildingCell = $building->cell;
    $visualCity->drawEllipse($buildingCell->y, $buildingCell->x, $ellipseSize, Colors::green5);
}

$visualCity->save($fileName."_buildings");