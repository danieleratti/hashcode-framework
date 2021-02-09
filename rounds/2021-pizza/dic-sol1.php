<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'e';

include 'dic-reader.php';

/** @var int $fourPeopleTeams */
/** @var int $threePeopleTeams */
/** @var int $twoPeopleTeams */

/** @var Collection $pizzas */
$pizzas = $pizzas->sort(function ($a, $b) {
    return count($a->ingredients) < count($b->ingredients);
});

// recursive
function findBestComb($maxPizzas, $pickedPizzas = [], $tollerance = 0): array
{
    global $pizzas;

    if (count($pickedPizzas) == $maxPizzas || $pizzas->count() == 0) {
        return $pickedPizzas;
    }

    $pizzaFound = false;

    foreach ($pizzas as $pizza) {
        $hasConflicts = false;

        // looks for conflicts
        foreach ($pickedPizzas as $pickedPizza) {
            $intersection = array_intersect($pizza->getIngredientNames(), $pickedPizza->getIngredientNames());

            if(count($intersection) > $tollerance) {
                $hasConflicts = true;
                break;
            }
        }

        if (!$hasConflicts) {
            $pickedPizzas[] = $pizza;
            $pizzas->forget($pizza->id);
            $pizzaFound = true;
            break;
        }
    }

    if(!$pizzaFound) {
        return findBestComb($maxPizzas, $pickedPizzas, $tollerance + 1);
    }

    return findBestComb($maxPizzas, $pickedPizzas);
}

$combinations = [];

for($i = 0; $i < $fourPeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
    }

    $bestPizzas = findBestComb(4);
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 4 people team – Missing: ' . ($fourPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for($i = 0; $i < $threePeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
    }

    $bestPizzas = findBestComb(3);
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 3 people team – Missing: ' . ($threePeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for($i = 0; $i < $twoPeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
    }

    $bestPizzas = findBestComb(2);
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 2 people team – Missing: ' . ($twoPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

Log::out('Finished');

createOutput($combinations, $fileName);

die();
