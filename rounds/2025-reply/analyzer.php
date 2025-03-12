<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

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

$fileName = '8';

include __DIR__ . '/reader.php';

$analyzer = new Analyzer($fileName, [
    'resources' => $resourcesCount,
    'turns' => $turnsCount,
    'initialCapital' => $initialCapital,
]);

$analyzer->addDataset('resources', $resources, [
    'activationCost',
    'periodicCost',
    'activeTurns',
    'downtimeTurns',
    'lifeCycle',
    'buildingsCapacity',
    'percentage',
]);

$analyzer->addDataset('turns', $turns, [
    'minBuildings',
    'maxBuildings',
    'profitPerBuilding',
]);

$analyzer->analyze();

die();
