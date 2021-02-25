<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\Log;

/** @var Collection|Pizza[] $PIZZAS */
/** @var Collection|Ingredient[] $PIZZAS */
/** @var array $TEAMS */
/** @var \Utils\FileManager $fileManager */
/** @var array $PIZZAS_HASH */

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = null;
$k1 = null;
Cerberus::runClient(['fileName' => 'a', 'k1' => 1]);
Autoupload::init();

include 'dr-reader.php';

// Vars
/** @var int $SCORE */
$SCORE = 0;
/** @var array $OUTPUT */
$OUTPUT = [];
$remainingTeams = $TEAMS[2] + $TEAMS[3] + $TEAMS[4];
$COMBINATIONS = [];

// Functions
function shipPizzas($pizzaIds)
{
    global $PIZZAS, $INGREDIENTS, $TEAMS, $OUTPUT, $SCORE, $remainingTeams, $PIZZAS_HASH;

    foreach ($pizzaIds as $id)
        if (!$PIZZAS[$id])
            return false;

    $calc = calcUniqueIngredientsByIds($pizzaIds);
    $score = $calc['score'];
    $SCORE += $score;
    $remainingTeams--;
    $TEAMS[count($pizzaIds)]--;
    $OUTPUT[] = $pizzaIds;

    foreach ($pizzaIds as $pizzaId) {
        $pizza = $PIZZAS[$pizzaId];
        foreach ($pizza->ingredientNames as $ingredientName) {
            /** @var Ingredient $i */
            $i = $INGREDIENTS[$ingredientName];
            unset($i->pizzas[$pizzaId]);
            $i->nPizzas--;
        }
        unset($PIZZAS_HASH[$pizza->hash][$pizza->id]);
        $PIZZAS->forget($pizza->id);
    }
    Log::out("Got $score with pizzas[" . implode(",", $pizzaIds) . "]! (TotalScore=$SCORE // RemainingTeams=" . $TEAMS[2] . "," . $TEAMS[3] . "," . $TEAMS[4] . " // RemainingPizzas=".$PIZZAS->count().")");
    return true;
}

function calcUniqueIngredientsByIds($pizzaIds)
{
    global $PIZZAS;
    $pizzas = [];
    foreach ($pizzaIds as $pizzaId)
        $pizzas[] = $PIZZAS[$pizzaId];
    return calcUniqueIngredients($pizzas);
}

function calcUniqueIngredients($pizzas)
{
    $ingredients = [];
    foreach ($pizzas as $pizza) {
        /** @var Pizza $pizza */
        $ingredients = array_merge($ingredients, $pizza->ingredientNames);
    }
    $countIngredients = array_count_values($ingredients);
    return [
        'uniqueIngredients' => count($countIngredients),
        'lostIngredients' => array_sum($countIngredients) - count($countIngredients),
        'totalIngredients' => array_sum($countIngredients),
        'score' => pow(count($countIngredients), 2),
    ];
}

function getOutput()
{
    global $OUTPUT;
    $ret = [];
    $ret[] = count($OUTPUT);
    foreach ($OUTPUT as $o) {
        $ret[] = count($o) . " " . implode(" ", $o);
    }
    return implode("\n", $ret);
}

function getCombination($pizzas)
{
    global $k1;
    $combination = [];
    $calc = calcUniqueIngredients($pizzas);
    $k = 1;
    foreach ($pizzas as $pizza) {
        $combination['id' . ($k++)] = $pizza->id;
        $combination['ids'][] = $pizza->id;
        $combination['idsCount']++;
    }
    foreach ($calc as $k => $v)
        $combination[$k] = $v;
    //$combination['myscore'] = $combination['score'] / (1 + pow($combination['lostIngredients'], 2));
    //$combination['myscore'] = $combination['score'] / (1 + pow($combination['lostIngredients'], 1.5));
    $combination['myscore'] = pow($combination['uniqueIngredients'], $k1) / $combination['totalIngredients'];
    $combination['pizzas'] = $pizzas;
    //$combination['myscore'] = $combination['score'];
    //$combination['myscore'] = $combination['score'] - pow($combination['lostIngredients'], 1.5);
    //$combination['myscore'] = $combination['score'] / (1+pow($combination['lostIngredients'], 1.5));
    return $combination;
}

function getBestCombination($teamSize = 2, $pizzas=null)
{
    global $PIZZAS;
    if(!$pizzas)
        $pizzas = [$PIZZAS->first()];
    for ($i = count($pizzas); $i < $teamSize; $i++) {
        $pizzas = addBestPizzaToCombination($pizzas);
    }
    return getCombination($pizzas);
}

function addBestPizzaToCombination($pizzas)
{
    global $PIZZAS;
    $noNewScore = 0;
    $bestPizza = null;
    $bestScore = 0;
    foreach ($PIZZAS as $pizzaNew) {
        foreach($pizzas as $pizzaOld)
            if($pizzaNew->id == $pizzaOld->id)
                continue 2;

        $comb = getCombination(array_merge($pizzas, [$pizzaNew]));
        if (!$bestPizza || $comb['myscore'] > $bestScore) {
            $bestScore = $comb['myscore'];
            $bestPizza = $pizzaNew;
            $noNewScore = 0;
        } else {
            $noNewScore++;
        }
        if($noNewScore > 20000)
            break;
    }
    return array_merge($pizzas, [$bestPizza]);
}

function shipBestCombination($teamSize = 2) {
    $comb = getBestCombination($teamSize);
    shipPizzas($comb['ids']);
}

$PIZZAS = $PIZZAS->sortByDesc('nIngredients');

while($remainingTeams > 0) {
    $comb = [];
    $bestComb = null;
    if ($TEAMS[2] > 0 && $PIZZAS->count() >= 2) $comb[2] = getBestCombination(2);
    if ($TEAMS[3] > 0 && $PIZZAS->count() >= 3) {
        if($comb[2])
            $comb[3] = getBestCombination(3, $comb[2]['pizzas']);
        else
            $comb[3] = getBestCombination(3);
    }
    if ($TEAMS[4] > 0 && $PIZZAS->count() >= 4) {
        if($comb[3])
            $comb[4] = getBestCombination(4, $comb[3]['pizzas']);
        else
            $comb[4] = getBestCombination(3);
    }
    foreach ($comb as $c)
        if (!$bestComb || $c['myscore'] > $bestComb['myscore'])
            $bestComb = $c;
    if($bestComb) {
        shipPizzas($bestComb['ids']);
    } else {
        break;
    }
}

$output = getOutput();
$fileManager->output($output, "k1_$k1--score_$SCORE");
//Log::out("Uploading SCORE=$SCORE ($fileName)...");
//Autoupload::submission($fileName, null, $output);
//Log::out("Uploaded SCORE=$SCORE ($fileName)...");
