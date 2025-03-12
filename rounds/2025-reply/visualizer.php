<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

$fileName = '3';

include __DIR__ . '/reader.php';

/** @var int $initialCapital */
global $initialCapital;
/** @var int $resourcesCount */
global $resourcesCount;
/** @var int $turnsCount */
global $turnsCount;
/** @var Turn[] $turns */
global $turns;
/** @var Resource[] $resources */
global $resources;

$minBuildings = PHP_INT_MAX;
$maxBuildings = 0;
$maxProfit = 0;
foreach ($turns as $turn) {
    if ($turn->minBuildings < $minBuildings) {
        $minBuildings = $turn->minBuildings;
    }
    if ($turn->maxBuildings > $maxBuildings) {
        $maxBuildings = $turn->maxBuildings;
    }
    if ($turn->profitPerBuilding > $maxProfit) {
        $maxProfit = $turn->profitPerBuilding;
    }
}

$chartHeight = 400;
$chartWidth = max($turnsCount, 1000);

$heightFactor = $chartHeight / 2 / $maxBuildings;
$widthFactor = $chartWidth / $turnsCount;
$profitFactor = $chartHeight / 2 / $maxProfit;

$visualGradient = new VisualStandard($chartHeight, $chartWidth);

$visualGradient->drawLine($chartHeight / 2 - 1, 0, $chartHeight / 2 - 1, $chartWidth - 1, Colors::black);

$prevMin = null;
$prevMax = null;
foreach ($turns as $turnId => $turn) {
    // Draw min and max buildings
    $newMin = [
        $chartHeight / 2 - (int)($turn->minBuildings * $heightFactor),
        (int)($turnId * $widthFactor)
    ];
    $newMax = [
        $chartHeight / 2 - (int)($turn->maxBuildings * $heightFactor),
        (int)($turnId * $widthFactor)
    ];
    $visualGradient->setPixel($newMin[0], $newMin[1], Colors::blue9);
    $visualGradient->setPixel($newMax[0], $newMax[1], Colors::red9);
    if ($prevMin !== null) {
        $visualGradient->drawLine($prevMin[0], $prevMin[1], $newMin[0], $newMin[1], Colors::blue9);
        $visualGradient->drawLine($prevMax[0], $prevMax[1], $newMax[0], $newMax[1], Colors::red9);
    }
    $prevMin = $newMin;
    $prevMax = $newMax;

    // Draw profit per building
    $visualGradient->drawLine($chartHeight - 1, (int)($turnId * $widthFactor), $chartHeight - 1 - ($turn->profitPerBuilding * $profitFactor), (int)($turnId * $widthFactor), Colors::green9);
}

$visualGradient->save($fileName);
