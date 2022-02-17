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
        $likesImportance = 1 / pow((count($c->likes) ?: 1), 1.0);
        $dislikesImportance = 2.2 / pow((count($c->dislikes) ?: 1), 2.8);
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

orderByImportance($ingredients);
$killsNumber = floor(count($ingredients) * 0.242);
$n = 0;
foreach ($ingredients as $k => $i) {
    if ($n <= $killsNumber) {
        foreach ($i->likedBy as $c) {
            unset($c->likes[$i->name]);
            unset($clients[$c->id]);
        }
        foreach ($i->dislikedBy as $c) {
            unset($c->dislikes[$i->name]);
        }
        unset($ingredients[$k]);
    }
    $n++;
}

$bestIngredients = $goodIngredients;
$bestScore = getScoreByIngredients($bestIngredients);

recalculateLikesAndDislikes();
orderByImportance($ingredients);

echo "Start at $bestScore points\n";

while (count($ingredients) > 0) {
    /** @var Ingredient $current */
    $current = array_pop($ingredients);
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
