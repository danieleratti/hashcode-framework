<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;

$fileName = $fileName ?: 'a';

/** @var Ingredient[] $ingredients */
$ingredients = [];
/** @var Pizza[] $pizzas */
$pizzas = [];
/** @var Team[] $teams */
$teams = [];
/** @var Team[][] $teamsBySize */
$teamsBySize = [];

class IngredientsManager
{
    /**
     * @param string $name
     * @return Ingredient
     */
    public static function getForName($name)
    {
        global $ingredients;
        if(!isset($ingredients[$name])) {
            $ingredients[$name] = new Ingredient($name);
        }
        return $ingredients[$name];
    }
}

class Ingredient
{
    /** @var string $name */
    public $name;

    /** @var Pizza[] $inPizzas */
    public $inPizzas;

    /**
     * Ingredient constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}

class PizzasManager
{
    /**
     * @param int $id
     * @param string[] $ingredients
     * @return Pizza
     */
    public static function create($id, $ingredients)
    {
        global $pizzas;
        $pizza = new Pizza($id, $ingredients);
        $pizzas[$id] = $pizza;
        return $pizza;
    }
}

class Pizza
{
    /** @var int $id */
    public $id;
    /** @var Ingredient[] $ingredients */
    public $ingredients = [];

    /**
     * Pizza constructor.
     * @param int $id
     * @param string[] $ingredients
     */
    public function __construct($id, $ingredients)
    {
        $this->id = $id;
        foreach ($ingredients as $i) {
            $ingredient = IngredientsManager::getForName($i);
            $ingredient->inPizzas[$id] = $this;
            $this->ingredients[] = $ingredient;
        }
    }
}

class TeamsManager
{
    private static $_nextId = 0;

    /**
     * @param int $id
     * @param int $size
     * @return Team
     */
    public static function create($size)
    {
        global $teams, $teamsBySize;
        $id = static::$_nextId++;
        $team = new Team($id, $size);
        $teams[$id] = $team;
        $teamsBySize[$size][] = $team;
        return $team;
    }
}

class Team
{
    /** @var int $id */
    public $id;
    /** @var int $size */
    public $size;
    /** @var Pizza[] $pizzas */
    public $pizzas = [];

    public function __construct($id, $size)
    {
        $this->id = $id;
        $this->size = $size;
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$pizzasCount, $T2, $T3, $T4] = explode(" ", $content[0]);
$pizzasCount = (int)$pizzasCount;
$T2 = (int)$T2;
$T3 = (int)$T3;
$T4 = (int)$T4;
array_shift($content);

for ($i = 0; $i < $pizzasCount; $i++) {
    $tempIngredients = explode(" ", $content[$i]);
    array_shift($tempIngredients);
    PizzasManager::create($i, $tempIngredients);
}

for ($i = 0; $i < $T2; $i++) {
    TeamsManager::create(2);
}
for ($i = 0; $i < $T3; $i++) {
    TeamsManager::create(3);
}
for ($i = 0; $i < $T4; $i++) {
    TeamsManager::create(4);
}

unset($content);

echo "a";
