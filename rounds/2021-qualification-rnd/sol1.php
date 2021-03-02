<?php

use Utils\FileManager;
use Utils\Log;

$fileName = 'b';

include 'reader.php';

/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Developer[] $DEVELOPERS */
/** @var FileManager $fileManager */
/** @var Map $MAP */

/** @var Company[] $COMPANIES */
foreach ($COMPANIES as $C) {
    $sum = 0;
    foreach ($C->inDevelopers as $dev) {
        $sum += $dev->bonus;
    }
    $C->mediumBonus = $sum / count($C->inDevelopers);
}

$COMPANIES = collect($COMPANIES)->keyBy('id');
$COMPANIES = $COMPANIES->sortByDesc('mediumBonus');

foreach ($COMPANIES as $C) {
    Log::out("Company id {$C->id}");
    foreach ($C->inDevelopers as $dev1) {
        foreach ($C->inDevelopers as $dev2) {
            if ($dev1->id == $dev2->id) {
                continue;
            }
            $C->couples[$dev1->id][$dev2->id] = count(array_unique(array_merge($dev1->skills, $dev2->skills))) * count(array_intersect($dev1->skills, $dev2->skills));
        }
    }
}

foreach ($MAP->map as $y => $row) {
    foreach ($row as $x => $cell) {
//        if ($MAP->map[$x][$y]->type == '_') {
//            $freeCells = $MAP->getFreeNeighbours($x, $y, '_');
//
//        }
        if($MAP->map[$x][$y]->type == '_') {
            $MAP->map[$x][$y]->assignedTo = '';
        }
    }
}

Log::out('Output...');
foreach ($DEVELOPERS as $k => $dev) {
    $y = $dev->posH;
    $x = $dev->posW;
    if ($y && $x)
        $output .= $x . ' ' . $y . PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
foreach ($PROJECTMANAGERS as $pm) {
    $y = $pm->posH;
    $x = $pm->posW;
    if ($y && $x)
        $output .= $x . ' ' . $y . PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
$fileManager->outputV2($output, time());

$test = $MAP->getFreeNeighbours(5, 0, '#');

die();
