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

function remove(array &$array, $value)
{
    for ($i = 0; $i < count($array); $i++) {
        if ($array[$i]->name === $value->name) {
            unset($array[$i]);
            return;
        }
    }
}

$fileName = 'a';

include_once 'reader.php';

$output = [];

// Code
$choosenIngredients = $ingredients;

// Remove disliked ingredients
foreach ($clients as $client) {
    foreach ($client->dislikes as $dislike) {
        remove($choosenIngredients, $dislike);
    }
}

// Points
Log::out('Score:' . getScoreByIngredients($choosenIngredients));

// Output
$output = [];
foreach ($choosenIngredients as $ingredient) {
    $output[] = $ingredient->name;
}
$output = array_unique($output);
$output = count($output) . ' ' . implode(' ', $output);
$fileManager->outputV2($output);
