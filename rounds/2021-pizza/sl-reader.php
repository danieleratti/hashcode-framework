<?php

use Utils\FileManager;
use Utils\Log;

/** @var Pizza[] $pizzas */
$pizzas= [];
/** @var Ingredient[] $ingredients */
$ingredients = [];

require_once '../../bootstrap.php';
$fileName = $fileName ?: 'a';

class Ingredient{
    public string $id;

    /** @var Pizza[] $pizzaContainers */
    public $pizzaContainers;

    public function __construct($id)
    {
        $this->id=$id;
    }

}

class Pizza{
    public int $id;
    /** @var Ingredient[] $ingredients */
    public $ingredients;

    public function __construct($id)
    {
        $this->id= $id;
    }
}

Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
list($pizzasCount, $teamTwo, $teamThree, $teamFour) = explode(' ', $content[0]);

$r=1;
for($i=1; $i<=$pizzasCount; $i++){
    $rowContent = explode(' ', $content[$i]);
    $newPizza = new Pizza(count($pizzas));
    foreach($rowContent as $k=>$v) {
        if ($k != 0) {
            if($ingredients[$v]===null)
                $ingredients[$v] = new Ingredient($v);
            $ingredients[$v]->pizzaContainers[] = $newPizza->id;
            $newPizza->ingredients[$v] = $ingredients[$v];
        }
    }
    $pizzas[] = $newPizza;
}
Log::out('reader finished');