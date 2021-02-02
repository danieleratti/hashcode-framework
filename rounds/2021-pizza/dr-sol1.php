<?php

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

// Functions
function shipPizzas($pizzaIds)
{
    global $PIZZAS, $INGREDIENTS, $TEAMS, $OUTPUT, $SCORE, $remainingTeams, $PIZZAS_HASH;

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
    }
    Log::out("Got $score! (TotalScore=$SCORE)");
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

/*
$output = getOutput();
$fileManager->output($output);
Autoupload::submission($fileName, 'dr-sol1', $output);
*/
