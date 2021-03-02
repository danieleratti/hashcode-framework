<?php

$fileName = 'a';

include __DIR__ . '/reader.php';
include_once __DIR__ . '/../../utils/Analysis/Analyzer.php';

/** @var \Utils\Collection $residences */
/** @var \Utils\Collection $utilities */

$analyzer = new Analyzer($fileName, [
    //'walking_distance' => $maxWalkingDistance,
    //'building_plans_count' => $buildingPlansCount,
]);
//$analyzer->addDataset('residences', $residences->toArray(), ['height', 'width', 'capacity', 'efficiency']);
//$analyzer->addDataset('utilities', $utilities->toArray(), ['height', 'width']);

$analyzer->analyze();
