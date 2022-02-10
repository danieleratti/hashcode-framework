<?php

global $fileName;
/** @var Client[] $clients */
global $clients;
/** @var Ingredient[] $ingredients */
global $ingredients;

use Utils\FileManager;

require_once '../../bootstrap.php';

class Client
{
    public int $id;
    public array $likes = [];
    /** @var $likesAsString string[] */
    public array $likesAsString = [];
    public array $dislikes = [];
    /** @var $dislikesAsString string[] */
    public array $dislikesAsString = [];
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
        if (!isset($ingredients[$name])) {
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
    $p = new Client();
    $p->id = $r;
    $ing = explode(' ', $content[$r * 2 + 1]);
    unset($ing[0]);
    $p->likes = getIngredients($ing);
    $p->likesAsString = array_map(fn($i) => $i->name, $p->likes);
    $ing = explode(' ', $content[$r * 2 + 2]);
    unset($ing[0]);
    $p->dislikes = getIngredients($ing);
    $p->dislikesAsString = array_map(fn($i) => $i->name, $p->dislikes);
    $clients[] = $p;
}
