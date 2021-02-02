<?php

use Utils\Collection;

$fileName = 'c';

include 'sz-reader.php';

/**
 * @var int $twoPeopleTeams
 * @var int $threePeopleTeams
 * @var int $fourPeopleTeams
 * @var Collection $pizzas
 * @var Collection $ingredients
 */

/** @var Pizza $pizza */
foreach ($pizzas as $pizza) {
    $nonRarity = 0;
    foreach ($pizza->ingredients as $ing) {
        $nonRarity += count($ing->inPizzas);
    }
    $pizza->quantityAndRarityScore = pow(count($pizza->ingredients), 2) / $nonRarity;
    $pizza->quantityOverRarityScore = pow(count($pizza->ingredients), 2) * $nonRarity;
}

$teams = [
    4 => $fourPeopleTeams,
    3 => $threePeopleTeams,
    2 => $twoPeopleTeams,
];

$orders = [
    4 => [],
    3 => [],
    2 => [],
];

foreach ($orders as $teamSize => $array) {
    $pizzas = $pizzas->sortBy('quantityAndRarityScore');

    for ($j = 0; $j < $teams[$teamSize]; $j++) {
        /** @var Pizza[] $order */
        $order = [];
        $order[] = $pizzas->pop();
        for ($i = 1; $i < $teamSize; $i++) {
            $pizzas = $pizzas->sortByDesc('quantityOverRarityScore');
            $maxAffinity = null;
            $targetPizza = null;
            foreach ($pizzas as $pizza) {
                $affinity = 0;
                foreach ($pizza->ingredients as $ingredient) {
                    if (!in_array($ingredient->name, array_column($order[$i - 1]->ingredients, 'name'))) {
                        $affinity++;
                    }
                }

                if ($maxAffinity === null || $affinity > $maxAffinity) {
                    $targetPizza = $pizza;
                }
            }
            $order[] = $pizzas->pull($targetPizza->id);
            if ($pizzas->count() === 0) {
                break;
            }
        }
        if ($pizzas->count() === 0) {
            break;
        }
        $orders[$teamSize][] = $order;
    }
}

$totOrders = count($orders[4]) + count($orders[3]) + count($orders[2]);
$outFile = 'sol-' . $fileName;
file_put_contents($outFile, $totOrders . PHP_EOL);
foreach ($orders[4] as $order) {
    file_put_contents($outFile, '4 ' . implode(' ', array_map(function ($x) {
            return $x->id;
        }, $order)), FILE_APPEND);
    file_put_contents($outFile, PHP_EOL, FILE_APPEND);
}
foreach ($orders[3] as $order) {
    file_put_contents($outFile, '3 ' . implode(' ', array_map(function ($x) {
            return $x->id;
        }, $order)), FILE_APPEND);
    file_put_contents($outFile, PHP_EOL, FILE_APPEND);
}
foreach ($orders[2] as $order) {
    file_put_contents($outFile, '2 ' . implode(' ', array_map(function ($x) {
            return $x->id;
        }, $order)), FILE_APPEND);
    file_put_contents($outFile, PHP_EOL, FILE_APPEND);
}
