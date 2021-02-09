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
