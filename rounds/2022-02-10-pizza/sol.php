<?php

use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager $fileManager */
global $fileManager;
/** @var Person[] $people */
global $people;
/** @var Ingredient[] $ingredients */
global $ingredients;

$fileName = 'a';

include_once 'reader.php';

$output = [];

// Codice
foreach ($ingredients as $i) {
    $output[] = $i->name;
}

// Output
$output = count($output) . ' ' . implode(' ', $output);
Log::out('Output...');
$fileManager->outputV2($output);
