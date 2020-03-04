<?php

$fileName = 'a';

include 'reader.php';

/** @var City $city */
$city->placeBuilding($buildings[0], 0, 0);
$city->placeBuilding($buildings[0], 0, 5);
$city->placeBuilding($buildings[1], 3, 0);
$city->placeBuilding($buildings[2], 0, 2);
$city->print();
echo $city->getScore();
