<?php

use Utils\Collection;

$fileName = 'b';

include 'sz-reader.php';

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

die();
