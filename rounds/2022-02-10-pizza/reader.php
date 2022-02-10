<?php

global $fileName;
/** @var Person[] $people */
global $people;
/** @var Ingredient[] $ingredients */
global $ingredients;

use Utils\FileManager;

require_once '../../bootstrap.php';

class Person
{
    public int $id;
    public array $likes = [];
    public array $dislikes = [];
}

class Ingredient
{
    public string $name;
}

/**
 * @param string[] $ing
 * @return Ingredient[]
 */
function getIngredients(array $ing): array
{
    global $ingredients;
    $result = [];
    foreach ($ing as $name) {
        if(!isset($ingredients[$name])) {
            $ing = new Ingredient();
            $ing->name = $name;
            $ingredients[$name] = $ing;
        }
        $result[] = $ingredients[$name];
    }
    return $result;
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

/** @var int $clientsNumber */
$clientsNumber = (int)$content[0];

for ($r = 0; $r < $clientsNumber; $r++) {
    $p = new Person();
    $p->id = $r;
    $ing = explode(' ', $content[$r * 2 + 1]);
    unset($ing[0]);
    $p->likes = getIngredients($ing);
    $ing = explode(' ', $content[$r * 2 + 2]);
    unset($ing[0]);
    $p->dislikes = getIngredients($ing);
    $people[] = $p;
}
