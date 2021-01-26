<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;
use Utils\Analysis\Analyzer;

$fileName = 'f';

require 'dr-reader.php';

$analyzer = new Analyzer($fileName, [
    'mount_points' => $N_MOUNT_POINTS,
    'assembly_points' => $N_ASSEMBLY_POINTS,
    'tasks' => $N_TASKS,
    'arms' => $N_ARMS,
    'steps' => $N_STEPS,
]);
$analyzer->addDataset('assembly_points', $ASSEMBLY_POINTS->toArray(), ['starts', 'finishes', 'middles', 'singles']);
$analyzer->addDataset('tasks', $TASKS->toArray(), ['score', 'nAssemblyPoints', 'nSteps', 'scorePerStep', 'offsettedScorePerStep']);
$analyzer->analyze();
