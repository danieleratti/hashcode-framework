<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = $fileName ?: 'a';

class Pizza
{
    private static $lastId = 0;
    public $id;
    public $ingredientNames;
    public $hash;
    public $nIngredients;

    public function __construct($ingredients)
    {
        $this->id = self::$lastId++;
        sort($ingredients);
        $this->ingredientNames = $ingredients;
        $this->hash = md5(implode(" ", $ingredients));
        $this->nIngredients = count($ingredients);
    }
}

class Ingredient
{
    public $ingredient;
    public $pizzas = [];
    public $nPizzas = 0;

    public static function put($ingredient, $pizza)
    {
        global $INGREDIENTS;
        $i = @$INGREDIENTS[$ingredient];
        if (!$i) {
            $i = new Ingredient();
            $i->ingredient = $ingredient;
            $INGREDIENTS[$ingredient] = $i;
        }
        $i->pizzas[$pizza->id] = $pizza;
        $i->nPizzas++;
        return $i;
    }
}

$N_PIZZAS = 0;
$PIZZAS = [];
$INGREDIENTS = [];
$PIZZAS_HASH = [];
$TEAMS = [2 => 0, 3 => 0, 4 => 0];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
list($N_PIZZAS, $TEAMS[2], $TEAMS[3], $TEAMS[4]) = explode(" ", $content[0]);
foreach ($content as $k => $v) if ($k >= 1) {
    $ingredients = explode(" ", $v);
    $ingredients = array_slice($ingredients, 1);
    $pizza = new Pizza($ingredients);
    $PIZZAS_HASH[$pizza->hash][$pizza->id] = $pizza;
    $PIZZAS[$pizza->id] = $pizza;
    foreach($ingredients as $ingredient)
        Ingredient::put($ingredient, $pizza);
}

foreach($TEAMS as $teamId => $n)
    $TEAMS[$teamId] = (int)$n;

$PIZZAS = collect($PIZZAS);
$INGREDIENTS = collect($INGREDIENTS);

$PIZZAS->keyBy('id');
$INGREDIENTS->keyBy('ingredient');

Log::out("Read finished");

