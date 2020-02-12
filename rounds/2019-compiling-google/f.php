<?php

$fileName = 'f';

require_once 'reader.php';

/** @var TargetFile[] $targets */

$output = [];
$s = 0;

/** @var TargetFile $target */
foreach ($targets as $target) {
    if ($target->file->compilingTime > $target->deadline)
        continue;

    foreach ($target->file->dependencies as $dep) {
        $output[] = $dep->id . " $s";
    }
    $output[] = $target->file->id . " $s";

    $s++;
}

$stringOut = count($output) . "\n" . implode("\n", $output);
verifyOutput($stringOut);
$fileManager->output($stringOut);
