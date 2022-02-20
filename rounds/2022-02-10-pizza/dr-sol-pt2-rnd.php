<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\FileManager;
use Utils\Log;
use Utils\Serializer;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

$fileName = 'd';

include_once 'dr-reader.php';
//include_once 'analyzer.php';
// Codice

/* Running vars */
$SCORE = 0;

/* Functions */
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
        $i->importanceL = 0.0;
        $i->importanceD = 0.0;
    }
    foreach ($clients as $c) {
        $likesImportance = 1 / (count($c->likes) ?: 1);
        $dislikesImportance = 2.2 / pow((count($c->dislikes) ?: 1), 2.8);
        foreach ($c->likes as $i) {
            $i->likedBy[] = $c;
            $i->importanceL += $likesImportance;
        }
        foreach ($c->dislikes as $i) {
            $i->dislikedBy[] = $c;
            $i->importanceD += $dislikesImportance;
        }
    }
    foreach ($ingredients as $i) {
        $i->importance = $i->importanceL / ($i->importanceD != 0 ? $i->importanceD : 1);
    }
}

function takeIngredient(Ingredient $ingredient)
{
    global $takenIngredients, $freeIngredients;
    Log::out("Add ingredient " . $ingredient->name, 1);

    // take ingredient
    $ingredient->taken = true;
    unset($freeIngredients[$ingredient->name]);
    $takenIngredients[$ingredient->name] = $ingredient;

    // add clients
    foreach ($ingredient->likedBy as $client) {
        if ($client->taken) Log::error("client->taken can't happen");
        unset($client->missingLikes[$ingredient->name]);
        unset($ingredient->likedBy[$client->id]);
        if (count($client->missingLikes) == 0 && count($client->takenDislikes) == 0)
            takeClient($client);
    }

    // remove clients
    foreach ($ingredient->dislikedBy as $client) {
        $client->takenDislikes[$ingredient->name] = $ingredient;
        if ($client->taken) {
            releaseClient($client);
        }
    }
}

function releaseIngredient(Ingredient $ingredient) //Not working probably
{
    global $takenIngredients, $freeIngredients;
    Log::out("Release ingredient " . $ingredient->name, 1);

    $impactedClients = [];

    // release ingredient
    $ingredient->taken = false;
    unset($takenIngredients[$ingredient->name]);
    $freeIngredients[$ingredient->name] = $ingredient;

    // add clients
    foreach ($ingredient->dislikedBy as $client) {
        unset($client->takenDislikes[$ingredient->name]);
        if (count($client->missingLikes) == 0 && count($client->takenDislikes) == 0) {
            takeClient($client);
            $impactedClients[] = $client;
        }
    }

    // release clients
    foreach ($ingredient->initiallyLikedBy as $client) {
        $ingredient->likedBy[$client->id] = $client;
        $client->missingLikes[$ingredient->name] = $ingredient;
        if ($client->taken) {
            releaseClient($client);
            $impactedClients[] = $client;
        }
    }

    foreach($impactedClients as $client)
        recalculateIngredientImportanceFromClient($client);
}

function takeClient(Client $client)
{
    global $SCORE, $takenClients, $freeClients;
    $client->taken = true;
    unset($freeClients[$client->id]);
    $takenClients[$client->id] = $client;
    foreach ($client->dislikes as $ingredient)
        $ingredient->dislikedByTakenClients[$client->id] = $client;
    $SCORE++;
    Log::out("Taken client " . $client->id . " - SCORE=$SCORE", 2);
}

function releaseClient(Client $client)
{
    global $SCORE, $takenClients, $freeClients;
    $client->taken = false;
    unset($takenClients[$client->id]);
    $freeClients[$client->id] = $client;
    foreach ($client->dislikes as $ingredient)
        unset($ingredient->dislikedByTakenClients[$client->id]);
    $SCORE--;
    Log::out("Released client " . $client->id . " - SCORE=$SCORE", 2);
}

function recalculateIngredientImportanceFromClient(Client $client)
{
    foreach ($client->likes as $likedIngredient) {
        recalculateIngredientImportance($likedIngredient);
    }
    foreach ($client->dislikes as $dislikedIngredient) {
        recalculateIngredientImportance($dislikedIngredient);
    }
}

function recalculateIngredientImportance(Ingredient $ingredient)
{
    $importance = 0;
    $importanceN = 0;
    $importanceD = 0;
    if ($ingredient->taken) {
        // taken -> quanto è importante tenerlo
        foreach ($ingredient->likedBy as $client) {
            if ($client->taken)
                $importanceN += 1; // tune
            else
                $importanceN += 1 / (count($client->missingLikes) + count($client->takenDislikes)); // tune
        }
        foreach ($ingredient->dislikedBy as $client) {
            $importanceN -= 1 / (count($client->missingLikes) + count($client->takenDislikes)); // tune
        }
        $importance = $importanceN; // tune
    } else {
        // free -> quanto è importante inserirlo
        foreach ($ingredient->likedBy as $client) {
            $importanceN += 1 / (count($client->missingLikes) + count($client->takenDislikes)); // tune
        }
        foreach ($ingredient->dislikedBy as $client) {
            if ($client->taken)
                $importanceN -= 1;
            else
                $importanceN -= 1 / (count($client->missingLikes) + count($client->takenDislikes)); // tune
        }
        $importance = $importanceN; // tune
    }
    $ingredient->importance = $importance;
}

$totalIngredients = $ingredients;
$totalClients = $clients;

$takenIngredients = [];
$freeIngredients = $ingredients;

$takenClients = [];
$freeClients = $clients;

/*
takeIngredient($freeIngredients['ingredient9922']);
takeIngredient($freeIngredients['ingredient3341']);
takeIngredient($freeIngredients['ingredient7670']);
takeIngredient($freeIngredients['ingredient7324']);
takeIngredient($freeIngredients['ingredient9885']);
releaseIngredient($takenIngredients['ingredient9885']);
takeIngredient($freeIngredients['ingredient9885']);
takeIngredient($freeIngredients['ingredient4654']);
releaseIngredient($takenIngredients['ingredient4654']);
die();

foreach ($freeIngredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        $goodIngredients[$k] = $i;
        unset($ingredients[$k]);
    }
}
*/

foreach ($ingredients as $ingredient)
    recalculateIngredientImportance($ingredient);

foreach ($freeIngredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        takeIngredient($i);
    }
}

$pt1 = Serializer::get('pt1-'.$fileName);
foreach ($pt1 as $i) {
    if($freeIngredients[$i])
        takeIngredient($freeIngredients[$i]);
}

Log::out("Algo pt2", 0, 'green', 'yellow');

$t = time();
while(true) {
    $preScore = $SCORE;
    $toRelease = $takenIngredients[array_rand($takenIngredients, 1)];
    $toTake = $freeIngredients[array_rand($freeIngredients, 1)];
    Log::out("1) Score prerelease SCORE=$SCORE", 2, 'purple', 'yellow');
    releaseIngredient($toRelease);
    Log::out("2) Score pretake SCORE=$SCORE", 2, 'purple', 'yellow');
    takeIngredient($toTake);
    if($SCORE < $preScore) {
        Log::out("Revert beacause less score => $SCORE!", 0, "red");
        Log::out("3) Score pre re-relase SCORE=$SCORE", 2, 'purple', 'yellow');
        releaseIngredient($toTake);
        Log::out("4) Score pre re-take SCORE=$SCORE", 2, 'purple', 'yellow');
        takeIngredient($toRelease);
        Log::out("4) Final SCORE=$SCORE", 2, 'purple', 'yellow');
        Log::out("Now score should be => $SCORE = $preScore!", 0, "purple");
        if($SCORE != $preScore)
            sleep(10);
    } elseif($SCORE > $preScore) {
        Log::out("New best score => $SCORE!", 0, "green");
        if($SCORE > 1773) {
            Log::out("Uploading!", 0, "green");
            $output = count($takenIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $takenIngredients));
            $fileManager->outputV2($output);
            Autoupload::submission($fileName, null, $output);
        }
    } else {
        Log::out("Stablescore => $SCORE!", 0, "yellow");
    }
}

/*
// Output
$output = count($reversedIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $reversedIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
Autoupload::submission($fileName, null, $output);
*/

