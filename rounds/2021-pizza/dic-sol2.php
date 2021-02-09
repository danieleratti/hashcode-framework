<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'b';

include 'dic-reader.php';

class Combination
{
    public $score = 0;
    public $pizzas = [];
    public $uniqueIngredients = [];
}

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


    foreach ($pizzas as $pizza) {
        $bestPizza = null;
        $bestScore = 0;

        $score=0;
        $takenIngred = [];
        // looks for conflicts
        foreach ($pickedPizzas as $pickedPizza) {
            $intersection = array_intersect($pizza->getIngredientNames(), $pickedPizza->getIngredientNames());
            //$score = count($pickedPizza->ingredients) + count($pizza->ingredients) - count($intersection);
            $takenIngred = array_merge($takenIngred, $pickedPizza->ingredients);

        }
        $common= array_intersect($takenIngred, $pizza->ingredients);
        $takenIngred = array_merge($takenIngred, $pizza->ingredients);
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

Log::out('Start looking for best comb');

$bestComb = findBestComb(4);

Log::out('Best comb found');

die();
