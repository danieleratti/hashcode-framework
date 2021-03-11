<?php

use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include 'rnd-reader.php';

/** @var Collection $BUILDINGS */
/** @var Collection $ANTENNAS */
/** @var Map $MAP */


$ellipseSize = 20;

$visualCity = new VisualStandard($MAP->height, $MAP->width);
foreach($BUILDINGS as $building) {
    $buildingCell = $building->cell;
    $visualCity->drawEllipse($buildingCell->y, $buildingCell->x, $ellipseSize, Colors::green5);
}

foreach($ANTENNAS as $antenna) {
    $cell = $antenna->cell;
    $visualCity->drawEllipse($cell->y, $cell->x, $ellipseSize, Colors::red6);
}
$visualCity->save($fileName."_buildings");