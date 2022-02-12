<?php

use Utils\FileManager;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

$fileName = 'e';

include_once 'mm-reader.php';
//include_once 'analyzer.php';
// Codice

//printArray($ingredients);
//die();

function recalculateLikesAndDislikes()
{
    /** @var Client[] $clients */
    global $clients;
    /** @var Ingredient[] $ingredients */
    global $ingredients;
    foreach ($ingredients as $i) {
        $i->likedBy = [];
        $i->dislikedBy = [];
        $i->importance = 0.0;
    }
    foreach ($clients as $c) {
        $likesImportance = 1 / pow((count($c->likes) ?: 1), 0.7);
        $dislikesImportance = 1 / pow((count($c->dislikes) ?: 1), 0.6);
        foreach ($c->likes as $i) {
            $i->likedBy[] = $c;
            $i->importance += $likesImportance;
        }
        foreach ($c->dislikes as $i) {
            $i->dislikedBy[] = $c;
            $i->importance -= $dislikesImportance;
        }
    }
}

$goodIngredients = [];
foreach ($ingredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        $goodIngredients[$k] = $i;
        unset($ingredients[$k]);
    }
}

$bestIngredients = $goodIngredients;
$bestScore = getScoreByIngredients($bestIngredients);

echo "Start at $bestScore points\n";

while (count($ingredients) > 0) {
    recalculateLikesAndDislikes();
    orderByImportance($ingredients);
    /** @var Ingredient $current */
    $current = array_pop($ingredients);
    foreach ($current->likedBy as $l) {
        unset($l->likes[$current->name]);
    }
    foreach ($current->dislikedBy as $d) {
        unset($d->dislikes[$current->name]);
    }
    $goodIngredients[] = $current;
    $currentScore = getScoreByIngredients($goodIngredients);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore) {
        $bestScore = $currentScore;
        $bestIngredients = $goodIngredients;
    }
}

//printArray($bestIngredients);

echo "\n\n";
echo "Best score is $bestScore";

// Output
$output = count($bestIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $bestIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
