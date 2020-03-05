<?php

$fileName = 'c';

include 'reader.php';

/** @var int $maxWalkingDistance */
/** @var int $buildingPlansCount */
/** @var int $cityColumns */
/** @var int $cityRows */
/** @var \Utils\Collection $residences */
/** @var \Utils\Collection $utilities */
/** @var City $city */

function buildGrid(Building $building)
{
    global $maxWalkingDistance, $cityColumns, $cityRows;

    $rStep = $building->height * 2 + $maxWalkingDistance * 2;
    $cStep = $building->width * 2 + $maxWalkingDistance * 2 - 2;
    $place = [];
    for ($r = 0; $r < $cityRows; $r += $rStep) {
        for ($c = 0; $c < $cityColumns; $c += $cStep) {
            $place[] = [$r, $c];
            $place[] = [$r + $maxWalkingDistance + $building->height, $c + $maxWalkingDistance + $building->width - 1];
        }
    }
    return $place;
}

/** @var Utility $utility */
$utility = $utilities[150];

foreach (buildGrid($utility) as $cell) {
    $city->placeBuilding($utility, $cell[0], $cell[1]);
}

$city->printUtilityCoverage($utility->utilityType);
echo $city->getScore();
