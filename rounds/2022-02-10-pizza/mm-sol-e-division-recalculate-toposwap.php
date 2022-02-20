<?php

use Utils\Autoupload;
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

//\Utils\Autoupload::submission("b", "prova", "provasub");
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
        $i->importance = $i->importanceL / ($i->importanceD!=0?$i->importanceD:1);
    }
}

$initialIngredients = $ingredients;

$goodIngredients = [];
foreach ($ingredients as $k => $i) {
    if (count($i->dislikedBy) === 0) {
        $goodIngredients[$k] = $i;
        unset($ingredients[$k]);
    }
}

$badIngredients = [];
$badClients = [];

orderByImportance($ingredients);
$killsNumber = floor(count($ingredients) * 0.242); //0.242
$n = 0;
foreach ($ingredients as $k => $i) {
    if ($n <= $killsNumber) {
        foreach ($i->likedBy as $c) {
            unset($c->likes[$i->name]);
            $badClients[$c->id] = $c;
            unset($clients[$c->id]);
        }
        foreach ($i->dislikedBy as $c) {
            unset($c->dislikes[$i->name]);
        }
        $badIngredients[$k] = $i;
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

echo "\n\n";
echo "Best score is $bestScore";
echo "\n\nFine prima parte\n\n";
sleep(2);

$reversedIngredients = array_reverse($bestIngredients, true);
$bestScore2 = $bestScore;

// Aggiungo ingredienti che avevo tolto all'inizio
foreach ($badClients as $k => $c) {
    $clients[$k] = $c;
}
foreach ($badIngredients as $k => $i) {
    $temp = $reversedIngredients;
    $temp[$k] = $i;
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}
/*
foreach ($badIngredients as $k => $i) {
    $temp = $reversedIngredients;
    $temp[$k] = $i;
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}

// Tolgo ingredienti a campione
foreach ($reversedIngredients as $k => $i) {
    $temp = $reversedIngredients;
    unset($temp[$k]);
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}
foreach ($reversedIngredients as $k => $i) {
    $temp = $reversedIngredients;
    unset($temp[$k]);
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}

// Aggiungo ingredienti che avevo tolto all'inizio
foreach ($badIngredients as $k => $i) {
    $temp = $reversedIngredients;
    $temp[$k] = $i;
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}
foreach ($badIngredients as $k => $i) {
    $temp = $reversedIngredients;
    $temp[$k] = $i;
    $currentScore = getScoreByIngredients($temp);
    echo "Score is $currentScore points\n";
    if ($currentScore > $bestScore2) {
        $bestScore2 = $currentScore;
        $reversedIngredients = $temp;
    }
}
*/
/** Random Topo Swapper */
$bestScore3 = $bestScore2;
$swappedIngredients = $reversedIngredients;
while(true) {
    $tentativeIngredients = $swappedIngredients;
    
    $tentativeScore = getScoreByIngredients($tentativeIngredients);
    echo "Score is $tentativeScore points\n";
    if ($tentativeScore > $bestScore3) {
        $bestScore3 = $tentativeScore;
        $swappedIngredients = $tentativeIngredients;
    }
}



//printArray($bestIngredients);

echo "\n\n";
echo "Best score is $bestScore2";

// Output
$output = count($reversedIngredients) . ' ' . implode(' ', array_map(fn($i) => $i->name, $reversedIngredients));
//Log::out('Output...');
$fileManager->outputV2($output);
Autoupload::submission($fileName, null, $output);
