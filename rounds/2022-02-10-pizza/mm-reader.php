<?php

global $fileName;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

use Utils\FileManager;

require_once '../../bootstrap.php';

class Client
{
    public int $id;
    public array $likes = [];
    /** @var string[] */
    public array $likesAsString = [];
    public array $dislikes = [];
    /** @var string[] */
    public array $dislikesAsString = [];

    public function __toString()
    {
        return 'C-' . str_pad($this->id, 10, ' ', STR_PAD_LEFT) . ' L[' . count($this->likes) . '] D[' . count($this->dislikes) . ']';
    }
}

class Ingredient
{
    public string $name;
    /** @var Client[] */
    public array $likedBy = [];
    /** @var Client[] */
    public array $dislikedBy = [];
    /** @var float */
    public float $importance = 0.0;

    public function __toString()
    {
        return $this->name . ' L[' . count($this->likedBy) . '] D[' . count($this->dislikedBy) . '] I[' . $this->importance . ']';
    }
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

/** @var int */
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

recalculateLikesAndDislikes();

function getIngredientsName($ings)
{
    return array_map(function ($i) {
        return $i->name;
    }, $ings);
}

function getScoreByIngredients($ings)
{
    global $clients;
    $score = 0;

    $check = [];
    foreach ($ings as $i) {
        $check[$i->name] = true;
    }

    foreach ($clients as $client) {
        foreach ($client->likesAsString as $like) {
            if (!isset($check[$like])) {
                continue 2;
            }
        }
        foreach ($client->dislikesAsString as $dislike) {
            if (isset($check[$dislike])) {
                continue 2;
            }
        }
        $score++;
    }

    return $score;
}

function recalculateLikesAndDislikes()
{
    /** @var Client[] $clients */
    global $clients;
    /** @var Ingredient[] $ingredients */
    global $ingredients;
    foreach ($ingredients as $i) {
        $i->likedBy = [];
        $i->dislikedBy = [];
        $i->importance = 0.0;
    }
    foreach ($clients as $c) {
        $likesImportance = 1 / (count($c->likes) ?: 1);
        $dislikesImportance = 1 / (count($c->dislikes) ?: 1);
        foreach ($c->likes as $i) {
            $i->likedBy[] = $c;
            $i->importance += $likesImportance;
        }
        foreach ($c->dislikes as $i) {
            $i->dislikedBy[] = $c;
            $i->importance -= $dislikesImportance;
        }
    }
}

/**
 * @param Ingredient[] $ingredients
 */
function orderByLikedAndDislikes(array &$ingredients)
{
    usort($ingredients, fn(Ingredient $i1, Ingredient $i2) => count($i1->dislikedBy) - count($i1->likedBy) < count($i2->dislikedBy) - count($i2->likedBy));
}

function printArray(?array $array)
{
    foreach ($array as $i) {
        echo $i . PHP_EOL;
    }
}
