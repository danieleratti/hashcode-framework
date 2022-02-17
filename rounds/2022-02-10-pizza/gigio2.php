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

$ingsTake = [];
$ingsAvailable = [];

foreach ($ingredients as $name => $ingredient) {
    $ingsTake[$name] = $ingredient;
}

$bestScore = 0;

while (true) {
    $impCout = 0;

    while (true) {
        $improved = false;

        foreach ($ingsAvailable as $name => $ing) {
            takeIng($ing);
            $newScore = getScoreByIngredients($ingsTake);

            if ($newScore > $bestScore) {
                Log::out("add $name $newScore");
                $improved = true;
                $bestScore = $newScore;
                $impCout++;
            } else {
                removeIng($ing);
            }
        }

        if (!$improved)
            break;
    }

    while (true) {
        $improved = false;

        foreach ($ingsTake as $name => $ing) {
            removeIng($ing);
            $newScore = getScoreByIngredients($ingsTake);

            if ($newScore > $bestScore) {
                Log::out("remove $name $newScore");
                $improved = true;
                $bestScore = $newScore;
                $impCout++;
            } else {
                takeIng($ing);
            }
        }

        if (!$improved)
            break;
    }

    if ($impCout == 0)
        break;
}

saveOut($ingsTake);

function takeIng($ing)
{
    global $ingsTake;
    global $ingsAvailable;

    $ingsTake[$ing->name] = $ing;
    unset($ingsAvailable[$ing->name]);
}

function removeIng($ing)
{
    global $ingsTake;
    global $ingsAvailable;

    $ingsAvailable[$ing->name] = $ing;
    unset($ingsTake[$ing->name]);
}
