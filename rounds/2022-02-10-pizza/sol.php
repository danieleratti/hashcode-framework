<?php

use Utils\FileManager;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

$fileName = 'c';

include_once 'reader.php';
//include_once 'analyzer.php';
// Codice

$goodIngredients = [];
foreach ($ingredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        $goodIngredients[$k] = $i;
        unset($ingredients[$k]);
    }
}

$sortedIngredients = array_values($ingredients);
usort($sortedIngredients, fn(Ingredient $i1, Ingredient $i2) => count($i1->dislikedBy) - count($i1->likedBy) < count($i2->dislikedBy) - count($i2->likedBy));

$i = 0;
$lastScore = 0;
$lastIngredients = $sortedIngredients;
$currentScore = 0;
while (count($sortedIngredients) > 0) {
    /** @var Ingredient $current */
    $current = $sortedIngredients[$i];
    if (count($current->dislikedBy) > 0) {
        unset($sortedIngredients[$i]);
        $currentScore = getScoreByIngredients($sortedIngredients);
        if ($currentScore < $lastScore) {
            break;
        }
        $lastScore = $currentScore;
        $lastIngredients = $sortedIngredients;
    } else {
        break;
    }
    $i++;
}


foreach ($goodIngredients as $i) {
    echo $i . PHP_EOL;
}

echo "\n\n";
echo $lastScore;

// Output
$output = count($lastIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $lastIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
