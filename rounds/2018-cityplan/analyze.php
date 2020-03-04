<?php

$fileName = 'f';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var \Utils\Collection $residences */
/** @var \Utils\Collection $utilities */

$analyzer = new Analyzer($fileName, [
    'walking_distance' => $maxWalkingDistance,
    'building_plans_count' => $buildingPlansCount,
]);
$analyzer->addDataset('residences', $residences->toArray(), ['height', 'width', 'capacity']);
$analyzer->addDataset('utilities', $utilities->toArray(), ['height', 'width']);

$analyzer->analyze();
