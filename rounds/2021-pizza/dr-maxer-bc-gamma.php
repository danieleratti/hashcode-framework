<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Collection;
use Utils\Log;

$fileName = 'b';

/** @var Collection|Pizza[] $PIZZAS */
/** @var Collection|Ingredient[] $PIZZAS */
/** @var array $TEAMS */
/** @var \Utils\FileManager $fileManager */
/** @var array $PIZZAS_HASH */

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

    foreach($pizzaIds as $id)
        if(!$PIZZAS[$id])
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
    $combination = [];
    $calc = calcUniqueIngredients($pizzas);
    $k = 1;
    foreach($pizzas as $pizza) {
        $combination['id' . ($k++)] = $pizza->id;
        $combination['ids'][] = $pizza->id;
        $combination['idsCount']++;
    }
    foreach ($calc as $k => $v)
        $combination[$k] = $v;
    //$combination['myscore'] = $combination['score'] / (1 + pow($combination['lostIngredients'], 2));
    $combination['myscore'] = $combination['score'] - pow($combination['lostIngredients'], 4);
    //$combination['myscore'] = $combination['score'];
    return $combination;
}

Log::out('Ordering level 2');
foreach ($PIZZAS as $id1 => $pizza1) {
    if($id1%100==0)
        Log::out("id1=$id1", 2);
    foreach ($PIZZAS as $id2 => $pizza2) {
        if ($id2 > $id1) {
            $combination = getCombination([$pizza1, $pizza2]);
            $COMBINATIONS[2][] = $combination;
        }
    }
}
ArrayUtils::array_keysort($COMBINATIONS[2], 'myscore', 'DESC');

Log::out('Ordering level 3');
foreach(array_slice($COMBINATIONS[2], 0, 2000) as $c2) {
    $pizza1 = $PIZZAS[$c2['id1']];
    $pizza2 = $PIZZAS[$c2['id2']];
    foreach($PIZZAS as $id3 => $pizza3) {
        if($id3 != $pizza1->id && $id3 != $pizza2->id) {
            $combination = getCombination([$pizza1, $pizza2, $pizza3]);
            $COMBINATIONS[3][] = $combination;
        }
    }
}
ArrayUtils::array_keysort($COMBINATIONS[3], 'myscore', 'DESC');

Log::out('Ordering level 4');
foreach(array_slice($COMBINATIONS[3], 0, 2000) as $c2) {
    $pizza1 = $PIZZAS[$c2['id1']];
    $pizza2 = $PIZZAS[$c2['id2']];
    $pizza3 = $PIZZAS[$c2['id3']];
    foreach($PIZZAS as $id4 => $pizza4) {
        if($id4 != $pizza1->id && $id4 != $pizza2->id && $id4 != $pizza3->id) {
            $combination = getCombination([$pizza1, $pizza2, $pizza3, $pizza4]);
            $COMBINATIONS[4][] = $combination;
        }
    }
}
ArrayUtils::array_keysort($COMBINATIONS[4], 'myscore', 'DESC');

Log::out('Assigning');
foreach($COMBINATIONS[4] as $comb) {
    if($TEAMS[$comb['idsCount']] > 0) {
        $r = shipPizzas($comb['ids']);
    }
}

foreach($COMBINATIONS[3] as $comb) {
    if($TEAMS[$comb['idsCount']] > 0) {
        $r = shipPizzas($comb['ids']);
    }
}

foreach($COMBINATIONS[2] as $comb) {
    if($TEAMS[$comb['idsCount']] > 0) {
        $r = shipPizzas($comb['ids']);
    }
}

//echo "Single Pizzas = ".count($PIZZAS_HASH)." / ".count($PIZZAS);

/*
 * 1,5s per MLN!
* /
for($i=0;$i<1000*1000;$i++) {
    \Utils\Stopwatch::tik('calcUniqueIngredientsByIds');
    calcUniqueIngredientsByIds([1, 2, 3, 4]);
    \Utils\Stopwatch::tok('calcUniqueIngredientsByIds');
}
\Utils\Stopwatch::print();
*/


Log::out('Uploading...');
$output = getOutput();
$fileManager->output($output);
Autoupload::submission($fileName, 'dr-sol1', $output);

