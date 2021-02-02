<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;

$fileName = 'e';

$ingredientsToPizzas = [];

class Pizza
{
    public int $id;
    public array $ingredients;
    public int $count;

    public function __construct($id, $ingredients)
    {
        global $ingredientsToPizzas;
        $this->id = $id;
        $this->ingredients = $ingredients;

        foreach ($ingredients as $ingredient) {
            $ingredientsToPizzas[$ingredient][] = $id;
        }

        $this->count = count($ingredients);
    }
}

$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
list($M, $T2, $T3, $T4) = explode(" ", $content[0]);
$content = array_slice($content, 1);

$pizzas = collect();
for ($i = 0; $i < count($content); $i++) {
    $ingredients = array_slice(explode(' ', $content[$i]), 1);
    $pizza = new Pizza($i, $ingredients);
    $pizzas[] = $pizza;
}

$allPizzas = collect($pizzas)
    ->keyBy('id');

$availablePizzas = collect($pizzas)
    ->sortByDesc('count')
    ->keyBy('id');

$teams = [
    [
        'members' => 2,
        'count' => $T2,
    ],
    [
        'members' => 3,
        'count' => $T3,
    ],
    [
        'members' => 4,
        'count' => $T4,
    ],
];

$output = [];

/** @return Pizza */
function findBestPizza($actualPizzasIds)
{
    global $availablePizzas, $allPizzas;

    $alreadyUsedIngredients = array_unique(array_merge(...array_map(function ($id) use ($allPizzas) {
        return $allPizzas->get($id)->ingredients;
    }, $actualPizzasIds)));

    $points = collect();
    foreach ($availablePizzas as $availablePizza) {
        $points->add([
            'id' => $availablePizza->id,
            'points' => count(array_diff($availablePizza->ingredients, $alreadyUsedIngredients))
        ]);
    }
    $best = $points->sortByDesc('points')->first();
    return $allPizzas[$best['id']];
}

function forgetPizza(Pizza $pizza)
{
    global $availablePizzas;
    $availablePizzas->forget($pizza->id);
}

$output = [];
$maxOutputRows = $T2 + $T3 + $T4;

foreach ($teams as $team) {
    $members = $team['members'];
    $count = $team['count'];

    if ($availablePizzas->count() < $members || count($output) >= $maxOutputRows)
        break;

    $deliver = [];
    for ($i = 0; $i < $members; $i++) {
        $pizza = findBestPizza($deliver);
        $deliver[] = $pizza->id;
        forgetPizza($pizza);
    }

    $outRow = "$members " . implode(' ', $deliver);

    $output[] = $outRow;
}

$fileManager->output(
    count($output) . "\n" . implode("\n", $output)
);
