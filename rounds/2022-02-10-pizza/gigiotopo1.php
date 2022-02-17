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

$fileName = 'd';

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
            foreach ($ingsAvailable as $name2 => $ing2) {
                if ($name == $name2)
                    continue;

                takeIng($ing);
                takeIng($ing2);

                $newScore = getScoreByIngredients($ingsTake);

                if ($newScore > $bestScore) {
                    Log::out("add $name $newScore");
                    $improved = true;
                    $bestScore = $newScore;
                    $impCout++;
                } else {
                    removeIng($ing);
                    removeIng($ing2);
                }
            }
        }

        if (!$improved)
            break;
    }

    while (true) {
        $improved = false;

        foreach ($ingsTake as $name => $ing) {
            foreach ($ingsTake as $name2 => $ing2) {
                if ($name == $name2)
                    continue;

                removeIng($ing);
                removeIng($ing2);
                $newScore = getScoreByIngredients($ingsTake);

                if ($newScore > $bestScore) {
                    Log::out("remove $name $newScore");
                    $improved = true;
                    $bestScore = $newScore;
                    $impCout++;
                } else {
                    takeIng($ing);
                    takeIng($ing2);
                }
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
