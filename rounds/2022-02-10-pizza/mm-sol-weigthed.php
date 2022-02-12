<?php

use Utils\FileManager;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

$fileName = 'd';

include_once 'mm-reader.php';
//include_once 'analyzer.php';
// Codice

//printArray($ingredients);
//die();

$goodIngredients = [];
foreach ($ingredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        $goodIngredients[$k] = $i;
        unset($ingredients[$k]);
    }
}

$sortedIngredients = array_values($ingredients);
orderByLikedAndDislikes($sortedIngredients);

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


printArray($goodIngredients);

echo "\n\n";
echo $lastScore;

// Output
$output = count($lastIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $lastIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
