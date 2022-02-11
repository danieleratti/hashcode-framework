<?php

use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager $fileManager */
global $fileManager;
/** @var Client[] $clients */
global $clients;
/** @var Ingredient[] $ingredients */
global $ingredients;

function is_choosen(Ingredient $ingredient, array $choosenIngredients): bool
{
    foreach ($choosenIngredients as $choosenIngredient) {
        if ($choosenIngredient->name === $ingredient->name) {
            return true;
        }
    }
    return false;
}

function printClients()
{
    global $clients;
    foreach ($clients as $client) {
        echo $client . PHP_EOL;
    }
}

$fileName = 'e';

include_once 'reader.php';

$output = [];

// Codice
// Sort customers for dislikes number descending. If they have same dislikes number order by likes number descending.
usort($clients, function (Client $a, Client $b) {
    if (count($a->dislikes) === count($b->dislikes)) {
        return count($a->likes) > count($b->likes);
    }

    return count($a->dislikes) > count($b->dislikes);
});
// printClients();

$choosenIngredients = [];
foreach ($clients as $client) {
    foreach ($client->dislikes as $dislike) {
        if (!is_choosen($dislike, $choosenIngredients)) {
            continue 2;
        }
    }

    foreach ($client->likes as $ingredient) {
        $choosenIngredients[] = $ingredient;
    }
}

$output = [];
foreach ($choosenIngredients as $ingredient) {
    $output[] = $ingredient->name;
}
$output = array_unique($output);

// Output
$output = count($output) . ' ' . implode(' ', $output);
$fileManager->outputV2($output);

Log::out('Score:' . getScoreByIngredients($choosenIngredients));
