<?php

global $fileName;
global $_visualyze;
global $_analyze;

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Visual\VisualGradient;

require_once '../../bootstrap.php';

class Resource
{
    public int $id;
    public int $activationCost;
    public int $periodicCost;
    public int $activeTurns;
    public int $downtimeTurns;
    public int $lifeCycle;
    public int $buildingsCapacity;
    public string $specialEffect;
    public ?int $percentage;
}

class Turn
{
    public int $id;
    public int $minBuildings;
    public int $maxBuildings;
    public int $profitPerBuilding;
}

enum SpecialEffect: string
{
    case A_SMART_METER = 'A';
    case B_DISTRIBUTION_FACILITY = 'B';
    case C_MAINTENANCE_PLAN = 'C';
    case D_RENEWABLE_PLANT = 'D';
    case E_ACCUMULATOR = 'E';
    case X_BASE_RESOURCE = 'X';
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
$fileRow = 0;

/** @var int $initialCapital */
/** @var int $resourcesCount */
/** @var int $turnsCount */
[$initialCapital, $resourcesCount, $turnsCount] = explode(' ', $content[$fileRow++]);
$initialCapital = (int)trim($initialCapital);
$resourcesCount = (int)trim($resourcesCount);
$turnsCount = (int)trim($turnsCount);

/** @var Resource[] $resources */
$resources = [];

for ($i = 0; $i < $resourcesCount; $i++) {
    [$id, $activationCost, $periodicCost, $activeTurns, $downtimeTurns, $lifeCycle, $buildingsCapacity, $specialEffect, $percentage] = explode(' ', $content[$fileRow++]);
    $r = new Resource();
    $r->id = $id;
    $r->activationCost = $activationCost;
    $r->periodicCost = $periodicCost;
    $r->activeTurns = $activeTurns;
    $r->downtimeTurns = $downtimeTurns;
    $r->lifeCycle = $lifeCycle;
    $r->buildingsCapacity = $buildingsCapacity;
    $r->specialEffect = $specialEffect;
    $r->percentage = $percentage;
    $resources[$r->id] = $r;
}

/** @var Turn[] $turns */
$turns = [];

for ($i = 0; $i < $turnsCount; $i++) {
    [$minBuildings, $maxBuildings, $profitPerBulding] = explode(' ', $content[$fileRow++]);
    $t = new Turn();
    $t->id = $i;
    $t->minBuildings = $minBuildings;
    $t->maxBuildings = $maxBuildings;
    $t->profitPerBuilding = $profitPerBulding;
    $turns[$t->id] = $t;
}

//print_r($resources);
//print_r($turns);
