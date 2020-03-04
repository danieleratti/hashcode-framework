<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'b';

include 'reader.php';

for($i=0;$i<100;$i++)
    $city->placeBuilding($utilities->first(), 10+$i, 10);
$city->placeBuilding($residences->first(), 100, 100);
$city->placeBuilding($residences->first(), 101, 101);
$city->placeBuilding($residences->first(), 102, 102);
$city->print();
