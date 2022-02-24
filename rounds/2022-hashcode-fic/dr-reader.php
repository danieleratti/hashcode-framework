<?php

global $fileName;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

use Utils\ArrayUtils;
use Utils\FileManager;

require_once '../../bootstrap.php';

/* Classes */
class Client
{
    public int $id;
    public array $missingLikes = [];
    public array $likes = [];
    /** @var string[] */
    public array $likesAsString = [];
    public array $dislikes = [];
    public array $takenDislikes = [];
    /** @var string[] */
    public array $dislikesAsString = [];
    /** @var float */
    public float $importance = 0.0;
    /** @var float */
    public float $importanceL = 0.0;
    /** @var float */
    public float $importanceD = 0.0;
    /** @var bool */
    public bool $taken = false;

    public function __toString()
    {
        return 'C-' . str_pad($this->id, 10, ' ', STR_PAD_LEFT) . ' L[' . count($this->likes) . '] D[' . count($this->dislikes) . ']';
    }
}

class Ingredient
{
    public string $name;
    /** @var Client[] */
    public array $initiallyLikedBy = [];
    /** @var Client[] */
    public array $likedBy = [];
    /** @var Client[] */
    public array $dislikedBy = [];
    /** @var Client[] */
    public array $dislikedByTakenClients = [];
    /** @var float */
    public float $importance = 0.0;
    /** @var float */
    public float $importanceL = 0.0;
    /** @var float */
    public float $importanceD = 0.0;
    /** @var bool */
    public bool $taken = false;

    public function __toString()
    {
        return $this->name . ' L[' . count($this->likedBy) . '] D[' . count($this->dislikedBy) . '] I[' . $this->importance . ']';
    }
}

/* Functions */
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
        $result[$name] = $ingredients[$name];
    }
    return $result;
}

/* Reading the input */
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
    $p->missingLikes = $p->likes;
    $p->likesAsString = array_map(fn($i) => $i->name, $p->likes);
    foreach($p->likes as $i) {
        $i->likedBy[$p->id] = $p;
        $i->initiallyLikedBy[$p->id] = $p;
    }

    $ing = explode(' ', $content[$r * 2 + 2]);
    unset($ing[0]);
    $p->dislikes = getIngredients($ing);
    $p->dislikesAsString = array_map(fn($i) => $i->name, $p->dislikes);
    foreach($p->dislikes as $i) {
        $i->dislikedBy[$p->id] = $p;
    }

    $clients[$p->id] = $p;
}
