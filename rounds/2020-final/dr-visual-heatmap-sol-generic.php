<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\ArrayUtils;
use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

require 'dr-reader.php';

$plot = [];
$maxScore = 0;

$visualStandard = new VisualStandard($H, $W);
foreach ($ASSEMBLY_POINTS as $ap) {
    /** @var AssemblyPoint $ap */
    $totalScore = 0;
    $totalSteps = 0;
    foreach ($ap->startingTasks as $task) {
        /** @var Task $task */
        $totalScore += $task->score;
        $totalSteps += $task->nSteps;
    }
    $scorePerSteps = $totalScore / ($totalSteps + 30);
    if ($scorePerSteps > $maxScore)
        $maxScore = $scorePerSteps;
    if ($ap->starts > 0)
        $plot[] = ['x' => $ap->x, 'y' => $ap->y, 'score' => $scorePerSteps, 'steps' => $totalSteps, 'nStartingTasks' => count($ap->startingTasks)];
}


$TOTAL_ARMSTEPS = $N_ARMS * $N_STEPS;
Log::out("Total Arm*Steps = " . $TOTAL_ARMSTEPS . "/" . $TASKS->sum('nSteps'));

ArrayUtils::array_keysort($plot, 'score', SORT_DESC);

$ArmSteps = 0;

foreach ($MOUNT_POINTS as $mp) {
    $visualStandard->drawEllipse($mp->y, $mp->x, 3, Colors::blue1);
}

foreach ($plot as $p) {
    $ArmSteps += $p['steps'];
    if ($ArmSteps > $TOTAL_ARMSTEPS) break;
    Log::out("Elaborate point $p[x] $p[y] (score $p[score] // nStartingTasks $p[nStartingTasks])");
    foreach ($XY_ASSEMBLY_POINTS[$p['x']][$p['y']]->startingTasks as $task) {
        Log::out("Elaborate task {$task->id} @ point $p[x] $p[y]");
        /** @var Task $task */
        $color = Colors::randomColor();
        $lastX = null;
        $lastY = null;
        foreach ($task->assemblyPoints as $ap) {
            /** @var AssemblyPoint $ap */
            Log::out("Elaborate ap {$ap->x} {$ap->y} @ task {$task->id} @ point $p[x] $p[y]");
            if ($lastX !== null) {
                $visualStandard->drawLine($lastY, $lastX, $ap->y, $ap->x, $color);
            }
            $lastX = $ap->x;
            $lastY = $ap->y;
        }
    }
    $visualStandard->drawEllipse($p['y'], $p['x'], 3, Colors::black);
    $visualStandard->saveIncr('heatmap-sol-generic/' . $fileName . '/algo1');
}
