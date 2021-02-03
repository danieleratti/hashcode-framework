<?php

/** @noinspection PhpUndefinedVariableInspection */

$fileName = 'c';

/** @var Ingredient[] $ingredients */
/** @var Pizza[] $pizzas */
/** @var Team[] $teams */
/** @var Team[][] $teamsBySize */

require 'mm-reader.php';

// Algo

$windowSizes = [500, 20, 20];
CombinationsManager::init($windowSizes);

$totalScore = 0;
$pizzasDelivered = 0;
$availablePizzas = $pizzas;
$windowPizzas = [];

// Ordino le $availablePizzas
uasort($availablePizzas, function (Pizza $p1, Pizza $p2) {
    return count($p1->ingredients) < count($p2->ingredients);
});

foreach ([2, 3, 4] as $tc) {
    // Prendo le prime $windowSize pizze
    $availablePizzas = $windowPizzas + $availablePizzas;
    $windowPizzas = array_slice($availablePizzas, 0, $windowSize, true);

    foreach ($windowPizzas as $wp) {
        unset($availablePizzas[$wp->id]);
    }

    foreach ($teamsBySize[$tc] as $team) {
        echo $team->id ."\n";

        // Calcolo tutte le combinazioni per la dimensione del team attuale e lo score
        $combinations = CombinationsManager::create($windowPizzas, $tc);
        $bestScore = -1;
        $bestCombination = null;
        foreach ($combinations as $c) {
            if ($c->score > $bestScore) {
                $bestScore = $c->score;
                $bestCombination = $c;
            }
        }
        if ($bestScore === -1) {
            echo "Pizze finite (a).\n";
            break 2;
        }
        $totalScore += $bestScore * $bestScore;
        $pizzasDelivered += $tc;
        $team->pizzas = $bestCombination->pizzas;

        // Pulisco $windowPizzas
        foreach ($team->pizzas as $pizza) {
            unset($windowPizzas[$pizza->id]);
        }
        // Ripopolo $windowPizzas
        $tpi = 0;
        foreach ($availablePizzas as $id => $pizza) {
            $windowPizzas[$id] = $pizza;
            unset($availablePizzas[$id]);
            $tpi++;
            if($tpi >= $tc) break;
        }
        //array_push($windowPizzas, ...array_splice($availablePizzas, 0, $tc));
        if (count($windowPizzas) < $tc) {
            echo "Pizze finite (b).\n";
            break 2;
        }
    }
}

echo "Score [$fileName]: $totalScore";

// output
$output = [];
$output[] = count($teams);
foreach ($teams as $team) {
    $line = $team->size . "";
    foreach ($team->pizzas as $pizza) {
        $line .= ' ' . $pizza->id;
    }
    $output[] = $line;
}

$fileManager->output(implode("\n", $output), $totalScore . '__' . implode('_', $windowSizes));
