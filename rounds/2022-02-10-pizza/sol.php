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

$fileName = 'e';

include_once 'reader.php';
//include_once 'analyzer.php';

$output = [];

// Codice

$sortedIngredients = array_values($ingredients);
usort($sortedIngredients, fn(Ingredient $i1, Ingredient $i2) => count($i1->dislikedBy) < count($i2->dislikedBy));

$i = 0;
while (count($sortedIngredients) > 0) {
    /** @var Ingredient $current */
    $current = $sortedIngredients[$i];
    if(count($current->dislikedBy) > 0) {
        unset($sortedIngredients[$i]);
    } else {
        break;
    }
    $i++;
}

foreach ($sortedIngredients as $i) {
    echo $i . PHP_EOL;
}

echo "\n\n";
echo getScoreByIngredients($sortedIngredients);

// Output
$output = count($sortedIngredients) . ' ' . implode(' ', $output);
//Log::out('Output...');
$fileManager->outputV2($output);
