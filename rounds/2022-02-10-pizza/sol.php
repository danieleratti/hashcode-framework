<?php

use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

$fileName = 'a';

include_once 'reader.php';
//include_once 'analyzer.php';

$output = [];

// Codice

$sortedIngredients = $ingredients;
usort($sortedIngredients, fn(Ingredient $i1, Ingredient $i2) => count($i1->dislikedBy) < count($i2->dislikedBy));

foreach ($sortedIngredients as $i) {
    echo $i . PHP_EOL;
}

// Output
$output = count($output) . ' ' . implode(' ', $output);
Log::out('Output...');
$fileManager->outputV2($output);
