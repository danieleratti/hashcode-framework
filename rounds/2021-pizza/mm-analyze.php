<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;
use Utils\Analysis\Analyzer;

$fileName = 'e';

require 'mm-reader.php';

$analyzer = new Analyzer($fileName, [
    'pizzas_count' => $pizzasCount,
    'teams_count' => $T2+$T3+$T4,
    't2' => $T2,
    't3' => $T3,
    't4' => $T4,
]);
//$analyzer->addDataset('assembly_points', $ASSEMBLY_POINTS->toArray(), ['starts', 'finishes', 'middles', 'singles']);
//$analyzer->addDataset('tasks', $TASKS->toArray(), ['score', 'nAssemblyPoints', 'nSteps', 'scorePerStep', 'offsettedScorePerStep']);
$analyzer->analyze();
