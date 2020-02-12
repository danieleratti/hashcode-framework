<?php

$fileName = 'b';

require_once 'reader.php';

/** @var TargetFile[] $targets */

$output = [];

$s = 0;
foreach ($targets as $target) {
    $dependencies = collect($target->file->dependencies);

    $second = collect($dependencies->first()->dependencies)->first();
    $first = collect(collect($dependencies->first()->dependencies)->first()->dependencies)->first();

    $output[] = $first->id . " $s";
    $output[] = $second->id . " $s";
    foreach ($dependencies as $dep) {
        $output[] = $dep->id . " $s";
    }
    $output[] = $target->file->id . " $s";

    $s++;
}

$stringOut = count($output) . "\n" . implode("\n", $output);
verifyOutput($stringOut);
$fileManager->output($stringOut);
