<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'b';

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

    Log::out('findBestComb – maxPizzas: ' . $maxPizzas . ' – pickedPizzas: ' . count($pickedPizzas), 1);

    $bestPizza = null;
    $bestScore = 0;

    foreach ($pizzas as $pizza) {
        $takenIngred = [];

        // looks for conflicts
        foreach ($pickedPizzas as $pickedPizza) {
            //$intersection = array_intersect($pizza->getIngredientNames(), $pickedPizza->getIngredientNames());
            //$score = count($pickedPizza->ingredients) + count($pizza->ingredients) - count($intersection);
            $takenIngred = array_merge($takenIngred, $pickedPizza->getIngredientNames());

        }
        $common= array_intersect($takenIngred, $pizza->getIngredientNames());
        $takenIngred = array_merge($takenIngred, $pizza->getIngredientNames());
        $score= count($takenIngred) - count($common);
        if($score>$bestScore){
            $bestScore = $score;
            $bestPizza = $pizza;
        }

    }

    $pickedPizzas[] = $bestPizza;
    $pizzas->forget($bestPizza->id);

    $bestComb = findBestComb($maxPizzas, $pickedPizzas);
    return $bestComb;
}


$combinations = [];

for($i = 0; $i < $fourPeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(4);
    if($i===59)
        Log::out('test');
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 4 people team – Missing: ' . ($fourPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for($i = 0; $i < $threePeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(3);
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 3 people team – Missing: ' . ($threePeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

for($i = 0; $i < $twoPeopleTeams; $i++) {
    if($pizzas->count() == 0){
        Log::out('Sono finite le pizze disponibili');
        break;
    }

    $bestPizzas = findBestComb(2);
    $combinations[] = new Combination($bestPizzas);
    Log::out('Best comb found for 2 people team – Missing: ' . ($twoPeopleTeams - $i) . ' – Pizzas remaining: ' . count($pizzas));
}

createOutput($combinations, 'testSara');

die();
