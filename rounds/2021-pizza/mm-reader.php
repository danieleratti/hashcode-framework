<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;

/** @var string $fileName */
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
    public static function getForName(string $name): Ingredient
    {
        global $ingredients;
        if (!isset($ingredients[$name])) {
            $ingredients[$name] = new Ingredient($name);
        }
        return $ingredients[$name];
    }
}

class Ingredient
{
    /** @var string $name */
    public string $name;
    /** @var Pizza[] $inPizzas */
    public array $inPizzas;

    /**
     * Ingredient constructor.
     * @param string $name
     */
    public function __construct(string $name)
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
    public static function create(int $id, array $ingredients): Pizza
    {
        global $pizzas;
        $pizza = new Pizza($id, $ingredients);
        $pizzas[$id] = $pizza;
        return $pizza;
    }

    private static array $_uniqueCache = [];

    /**
     * @param Pizza[] $pizzas
     * @return array
     */
    public static function uniqueIngredients(array $pizzas): array
    {
        $cacheKey = array_reduce($pizzas, function ($a, Pizza $p) {
            return $a . '-' . $p->id;
        }, '');
        if (isset(static::$_uniqueCache[$cacheKey])) {
            return static::$_uniqueCache[$cacheKey];
        }

        $unique = [];
        foreach ($pizzas as $pizza) {
            foreach ($pizza->ingredients as $ingredient) {
                if (!isset($unique[$ingredient->name])) {
                    $unique[$ingredient->name] = $ingredient;
                }
            }
        }
        static::$_uniqueCache[$cacheKey] = $unique;

        return $unique;
    }
}

class Pizza
{
    /** @var int $id */
    public int $id;
    /** @var Ingredient[] $ingredients */
    public array $ingredients = [];

    /**
     * Pizza constructor.
     * @param int $id
     * @param string[] $ingredients
     */
    public function __construct(int $id, array $ingredients)
    {
        $this->id = $id;
        foreach ($ingredients as $i) {
            $ingredient = IngredientsManager::getForName($i);
            $ingredient->inPizzas[$id] = $this;
            $this->ingredients[$ingredient->name] = $ingredient;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasIngredient(string $name): bool
    {
        return isset($this->ingredients[$name]);
    }
}

class TeamsManager
{
    private static int $_nextId = 0;

    /**
     * @param int $size
     * @return Team
     */
    public static function create(int $size): Team
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
    public int $id;
    /** @var int $size */
    public int $size;
    /** @var Pizza[] $pizzas */
    public array $pizzas = [];

    /**
     * Team constructor.
     * @param int $id
     * @param int $size
     */
    public function __construct(int $id, int $size)
    {
        $this->id = $id;
        $this->size = $size;
    }
}

class CombinationsManager
{
    /**
     * @var int[][][]
     */
    private static array $cacheCombinationsIdx = [];
    /**
     * @var Combination[]
     */
    private static array $cacheCombinations = [];

    /**
     * @param int[] $windowSizes
     * @return void
     */
    public static function init(array $windowSizes): void
    {
        foreach ([2, 3, 4] as $k) {
            /*$indexes = range(0, $windowSize - 1);
            foreach (new Combinations($indexes, $k) as $c) {
                static::$cacheCombinationsIdx[$k][] = $c;
            }*/
            $indexes = range(1, $windowSizes[$k - 2] - 1);
            foreach (new Combinations($indexes, $k - 1) as $c) {
                static::$cacheCombinationsIdx[$k][] = array_merge([0], $c);
            }
        }
    }

    /**
     * @param Pizza[] $pizzas
     * @param int $count
     * @return Combination[]
     */
    public static function create(array $pizzas, int $count): array
    {
        $tempPizzas = array_values($pizzas);
        $combinations = [];
        foreach (static::$cacheCombinationsIdx[$count] as $cIdxs) {
            //$cacheKey = implode(',', $cIdxs);
            //if (isset(static::$cacheCombinations[$cacheKey])) {
            //    $combination = static::$cacheCombinations[$cacheKey];
            //} else {
                $combinationPizzas = [];
                foreach ($cIdxs as $cIdx) {
                    if(!isset($tempPizzas[$cIdx]))
                        continue 2;
                    $combinationPizzas[] = $tempPizzas[$cIdx];
                }
                $combination = new Combination($combinationPizzas);
                $combination->score = count(PizzasManager::uniqueIngredients($combinationPizzas));
                //static::$cacheCombinations[$cacheKey] = $combination;
            //}
            $combinations[] = $combination;
        }
        return $combinations;
    }
}

class Combination
{
    /** @var Pizza[] $pizzas */
    public array $pizzas = [];
    /** @var int $score */
    public int $score = 0;

    /**
     * Combination constructor.
     * @param array $pizzas
     */
    public function __construct(array $pizzas)
    {
        $this->pizzas = $pizzas;
    }
}

class Combinations implements Iterator
{
    protected $c = null;
    protected $s = null;
    protected $n = 0;
    protected $k = 0;
    protected $pos = 0;

    function __construct($s, $k)
    {
        $this->s = array_values($s);
        $this->n = count($this->s);
        $this->k = $k;
        $this->rewind();
    }

    function key()
    {
        return $this->pos;
    }

    function current()
    {
        $r = [];
        for ($i = 0; $i < $this->k; $i++)
            $r[] = $this->s[$this->c[$i]];
        return $r;
    }

    function next()
    {
        if ($this->_next())
            $this->pos++;
        else
            $this->pos = -1;
    }

    function rewind()
    {
        $this->c = range(0, $this->k);
        $this->pos = 0;
    }

    function valid()
    {
        return $this->pos >= 0;
    }

    protected function _next()
    {
        $i = $this->k - 1;
        while ($i >= 0 && $this->c[$i] == $this->n - $this->k + $i)
            $i--;
        if ($i < 0)
            return false;
        $this->c[$i]++;
        while ($i++ < $this->k - 1)
            $this->c[$i] = $this->c[$i - 1] + 1;
        return true;
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

