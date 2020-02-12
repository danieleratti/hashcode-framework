<?php

use Utils\PerfectFitter;

$fileName = 'c';

require_once 'reader.php';

/** @var TargetFile[] $targets */

foreach (collect($targets)->sortByDesc('goalPoints') as $target) {
    echo $target->file->id . " " . $target->goalPoints . " " . $target->deadline . "\n";
}

$theRealTarget = collect($targets)
    ->sortBy('goalPoints')
    ->last();

$items = [];

foreach ($theRealTarget->file->dependencies as $file) {
    $items[$file->id] = $file->compilingTime;
}

$output = [];
for ($i = 0; $i < 30; $i++) {
    echo "Fitter $i / 30 ";
    $fitter = new PerfectFitter($items, 3334);
    $result = $fitter->fit();
    foreach ($result as $id) {
        unset($items[$id]);
    }

    echo $result['message'] . "\n";

    foreach ($result['solution'] as $f) {
        $output[] = "$f $i";
    }
}
$output[] = $theRealTarget->file->id . " 0";
$stringOut = count($output) . "\n" . implode("\n", $output);
verifyOutput($stringOut);
$fileManager->output($stringOut);
