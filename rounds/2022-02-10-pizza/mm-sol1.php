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

$fileName = 'c';

include_once 'reader.php';
//include_once 'analyzer.php';
// Codice

orderByLikedAndDislikes($ingredients);
$i = 0;
$lastScore = 0;
$currentScore = 0;
while (count($ingredients) > 0) {
    /** @var Ingredient $current */
    $current = $ingredients[$i];
    if(count($current->dislikedBy) > 0) {
        unset($ingredients[$i]);
        $currentScore = getScoreByIngredients($ingredients);
        if($currentScore < $lastScore) {
            break;
        }
        $lastScore = $currentScore;
        $lastIngredients = $ingredients;
        recalculateLikesAndDislikes();
        orderByLikedAndDislikes($ingredients);
    } else {
        break;
    }
    $i++;
}


foreach ($lastIngredients as $i) {
    echo $i . PHP_EOL;
}

echo "\n\n";
echo $lastScore;

// Output
$output = count($lastIngredients) . ' ' . implode(' ', array_map(fn ($i) => $i->name, $lastIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
