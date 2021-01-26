<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\Visual\VisualGradient;

$fileName = 'f';

require 'dr-reader.php';

$plot = [];
$maxScore = 0;

$visualStandard = new VisualGradient($H, $W);
//foreach ($MOUNT_POINTS as $mp)
//    $visualStandard->setPixel($mp->y, $mp->x, Colors::blue9);
foreach ($ASSEMBLY_POINTS as $ap) {
    /** @var AssemblyPoint $ap */
    $totalScore = 0;
    $totalSteps = 0;
    foreach ($ap->startingTasks as $task) {
        /** @var Task $task */
        $totalScore += $task->score;
        $totalSteps += $task->nSteps;
    }
    $scorePerSteps = $totalScore / ($totalSteps + 10);
    if($scorePerSteps > $maxScore)
        $maxScore = $scorePerSteps;
    $plot[] = ['x' => $ap->x, 'y' => $ap->y, 'score' => $scorePerSteps];
}

foreach($plot as $p) {
    if($p['score'] >= $maxScore*0.5)
        $visualStandard->setPixel($p['y'], $p['x'], $p['score']/$maxScore);
}

$visualStandard->save($fileName . '-heatmap');
