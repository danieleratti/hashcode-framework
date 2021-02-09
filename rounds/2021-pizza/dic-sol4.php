<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'd';

include 'dic-reader.php';

/** @var int $fourPeopleTeams */
/** @var int $threePeopleTeams */
/** @var int $twoPeopleTeams */
/** @var Collection $pizzas */
$pizzas = $pizzas->sort(function ($a, $b) {
    return count($a->ingredients) < count($b->ingredients);
});

// recursive
function findBestComb($maxPizzas, $pickedPizzas = []): array
{
    global $pizzas;

    if (count($pickedPizzas) == $maxPizzas) {
        return $pickedPizzas;
    }

    $bestPizza = null;
    $bestScore = 0;

    foreach ($pizzas->slice(0, 500) as $pizza) {
        $uniqueIngredients = [];

        // looks for conflicts
        foreach ($pickedPizzas as $pickedPizza) {
            $uniqueIngredients = array_merge($uniqueIngredients, $pickedPizza->getIngredientNames());
        }

        $uniqueIngredients = array_merge($uniqueIngredients, $pizza->getIngredientNames());
        $totalIngredients = count($uniqueIngredients);

        $uniqueIngredients = array_unique($uniqueIngredients);
        $lostIngredients = $totalIngredients - count($uniqueIngredients);

        $score = count($uniqueIngredients) / $lostIngredients;

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestPizza = $pizza;
        }
    }

    if ($bestPizza == null) {
        return $pickedPizzas;
    }

    $pickedPizzas[] = $bestPizza;
    $pizzas->forget($bestPizza->id);

    return findBestComb($maxPizzas, $pickedPizzas);
}


$combinations = [];

for ($i = 0; $i < $twoPeopleTeams; $i++) {
    if ($pizzas->count() == 0) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(2);

    if (count($bestPizzas) < 2) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 2 people team – Missing: ' . ($twoPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for ($i = 0; $i < $threePeopleTeams; $i++) {
    if ($pizzas->count() == 0) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(3);

    if (count($bestPizzas) < 3) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 3 people team – Missing: ' . ($threePeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for ($i = 0; $i < $fourPeopleTeams; $i++) {
    if ($pizzas->count() == 0) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(4);

    if (count($bestPizzas) < 4) {
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 4 people team – Missing: ' . ($fourPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

createOutput($combinations, 'giorgiozem');

die();
