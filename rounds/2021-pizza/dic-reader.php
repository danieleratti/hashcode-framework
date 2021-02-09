<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Ingredient
{
    /**
     * @var string $name
     */
    public $name;

    /** @var Pizza[] $inPizzas */
    public $inPizzas;

    public function __construct($name)
    {
        $this->name = $name;
        $this->inPizzas = [];
    }
}

class Pizza
{
    /** @var int $id */
    public $id;
    /** @var Ingredient[] $ingredients */
    public $ingredients;

    public function __construct($id)
    {
        $this->id = $id;
        $this->ingredients = [];
    }

    public function getIngredientNames()
    {
        return array_map(function ($ingredient) {
            return $ingredient->name;
        }, $this->ingredients);
    }
}

class Combination
{
    /** @var int */
    public $score = 0;
    /** @var Pizza[] */
    public $pizzas = [];
    /** @var Ingredient[] */
    public $uniqueIngredients = [];

    public function __construct($pizzas)
    {
        $this->pizzas = $pizzas;

        foreach ($pizzas as $pizza) {
            /** @var Pizza $pizza */
            $intersection = array_intersect($pizza->getIngredientNames(), $this->uniqueIngredients);
            $toAdd = array_diff($pizza->getIngredientNames(), $intersection);
            $this->uniqueIngredients = array_merge($this->uniqueIngredients, $toAdd);
        }

        $this->score = pow(count($this->uniqueIngredients), 2);
    }
}

function createOutput($orders, $filename)
{
    $outFile = 'dic-sol-' . $filename . '_' . time();
    file_put_contents($outFile, count($orders) . PHP_EOL);
    foreach ($orders as $order) {
        /** @var Combination $order */
        file_put_contents($outFile, count($order->pizzas) . ' ' . implode(' ', array_map(function ($x) {
                return $x->id;
            }, $order->pizzas)), FILE_APPEND);
        file_put_contents($outFile, PHP_EOL, FILE_APPEND);
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$pizzas = [];
$ingredients = [];

list($countPizza, $twoPeopleTeams, $threePeopleTeams, $fourPeopleTeams) = explode(' ', $content[0]);
$countPizza = (int)$countPizza;
$twoPeopleTeams = (int)$twoPeopleTeams;
$threePeopleTeams = (int)$threePeopleTeams;
$fourPeopleTeams = (int)$fourPeopleTeams;

for ($i = 0; $i < $countPizza; $i++) {
    $pizzaRow = explode(' ', $content[$i + 1]);
    $pizzaIngredients = array_slice($pizzaRow, 1, count($pizzaRow));
    $pizza = new Pizza($i);
    foreach ($pizzaIngredients as $ingName) {
        if (!isset($ingredients[$ingName])) {
            $ingredients[$ingName] = new Ingredient($ingName);
        }
        $pizza->ingredients[] = $ingredients[$ingName];
        $ingredients[$ingName]->inPizzas[] = $pizza;
    }
    $pizzas[] = $pizza;
}

$pizzas = collect($pizzas)->keyBy('id');
$ingredients = collect($ingredients)->keyBy('name');
