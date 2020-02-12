<?php

use Utils\PerfectFitter;

$fileName = 'e';

require_once 'reader.php';

/** @var TargetFile[] $targets */

/** @var TargetFile $theRealTarget */
$theRealTarget = collect($targets)->last();

$items = [];

foreach ($theRealTarget->file->dependencies as $file) {
    $items[$file->id] = $file->compilingTime;
}

$output = [];
for ($i = 0; $i < 3; $i++) {
    $fitter = new PerfectFitter($items, 333333);
    $result = $fitter->fit();
    foreach ($result as $id) {
        unset($items[$id]);
    }

    foreach ($result['solution'] as $f) {
        $output[] = "$f $i";
    }
}
$output[] = $theRealTarget->file->id . " 0";
$stringOut = count($output) . "\n" . implode("\n", $output);
verifyOutput($stringOut);
$fileManager->output($stringOut);
