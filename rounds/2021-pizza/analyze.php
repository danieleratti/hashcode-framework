<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;

$fileName = 'e';

include 'sz-reader.php';

/**
 * @var int $twoPeopleTeams
 * @var int $threePeopleTeams
 * @var int $fourPeopleTeams
 * @var Collection $pizzas
 * @var Collection $ingredients
 */

$analyzer = new Analyzer($fileName, [
    'pizzas_count' => $pizzas->count(),
    'ingredients_count' => $ingredients->count(),
    'two_people_teams' => $twoPeopleTeams,
    'three_people_teams' => $threePeopleTeams,
    'four_people_teams' => $fourPeopleTeams,
]);
$analyzer->addDataset('pizzas', $pizzas, ['ingredients']);
$analyzer->addDataset('ingredients', $ingredients, ['inPizzas']);
$analyzer->analyze();
