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

function saveOut($ings)
{
    global $fileManager;

    $score = getScoreByIngredients($ings);
    Log::out('SCORE ' . $score);

    $output = getIngredientsName($ings);
    $output = count($output) . ' ' . implode(' ', $output);

    $fileManager->outputV2($output);
}


usort($ingredients, fn(Ingredient $i1, Ingredient $i2) => count($i1->dislikedBy) < count($i2->dislikedBy));
$remainingIngredients = [];
foreach ($ingredients as $name => $ingredient) {
    $remainingIngredients[$ingredient->name] = $ingredient;
}

$bestScore = getScoreByIngredients($remainingIngredients);
$maxCheck = 100;

do {
    $toBeRemoved = null;
    $i = 0;

    Log::out('COUNT: ' . count($remainingIngredients) . " - BEST: " . $bestScore);
    saveOut($remainingIngredients);

    foreach ($remainingIngredients as $ingName => $ingredient) {
        $remaining = $maxCheck ? min(count($remainingIngredients), $maxCheck) : count($remainingIngredients);

        unset($remainingIngredients[$ingName]);

        $newScore = getScoreByIngredients($remainingIngredients);

        if ($newScore > $bestScore) {
            $bestScore = $newScore;
            $toBeRemoved = $ingredient;
        }

        $remainingIngredients[$ingName] = $ingredient;

        if (!is_null($maxCheck) && $i >= $maxCheck)
            break;
    }

    unset($remainingIngredients[$toBeRemoved->name]);

} while (!is_null($toBeRemoved));
