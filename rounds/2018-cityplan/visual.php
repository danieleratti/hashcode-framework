<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

include 'reader.php';

function printVisual(Building $building, $r, $c, $showWD = false)
{
    global $visualStandard;

    foreach ($building->getRelativeWalkableArea($r, $c) as $cell) {
        $visualStandard->setPixel($cell[0], $cell[1], $building instanceof Utility ? Colors::blue1 : Colors::red1);
    }

    foreach ($building->getRelativeCellsList($r, $c) as $cell) {
        $visualStandard->setPixel($cell[0], $cell[1], $building instanceof Utility ? Colors::blue5 : Colors::red5);
    }
    foreach ($building->getRelativePerimeter($r, $c) as $cell)
        $visualStandard->setPixel($cell[0], $cell[1], $building instanceof Utility ? Colors::blue9 : Colors::red9);
}

$padding = 10 + $maxWalkingDistance * 2;

$maxResidenceHeight = $residences->max('height');
$residenceWidth = $residences->sum('width') + ($residences->count() + 1) * $padding;

$_maxUtilityHeight = $utilities->groupBy('utilityType')->map(function ($group) {
    return $group->max('height');
});
$maxUtilityHeight = $_maxUtilityHeight->count() * $padding + $_maxUtilityHeight->sum();
$maxUtilitiesWidth = $utilities->groupBy('utilityType')->map(function ($group) {
    return $group->sum('width');
})->max();

$rows = $padding * 15 + $maxResidenceHeight + $maxUtilityHeight;
$cols = $padding * 2 + max($residenceWidth, $maxUtilitiesWidth);

$visualStandard = new VisualStandard($rows, $cols);
$r = $padding;
$c = $padding;
foreach ($residences->sortByDesc('efficiency') as $residence) { //sort efficiency, capacity
    printVisual($residence, $r, $c);
    $c += $residence->width + $padding;
}

$r = $maxResidenceHeight + $padding * 2;
$deltaR = 0;
foreach ($utilities->groupBy('utilityType') as $utilityGroup) {
    $c = $padding;
    $r += $deltaR + $padding;
    foreach ($utilityGroup as $utility) {
        printVisual($utility, $r, $c);
        $c += $utility->width + $padding;
        $deltaR = max($deltaR, $utility->height);
    }
}

$visualStandard->save('visualEfficiency_' . $fileName);
