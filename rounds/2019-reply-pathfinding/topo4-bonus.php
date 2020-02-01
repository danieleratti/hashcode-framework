<?php

/*
 * Sampled algorithm
 *
 */

$fileName = '2';
$sampleSize = 1;

include 'reader.php';

$reverseClients = [];
$foundClients = [];

$bonus = 0;
/** @var PathMap $pathMap */
foreach ($caches as $pathMap) {
    $bonus += $pathMap->client->revenue;
    $reverseClients[$pathMap->client->id] = 1;
}

$possibilities = collect();
for ($r = 0; $r < $map->rowCount; $r += $sampleSize) {
    echo "R=$r\n";
    for ($c = 0; $c < $map->colCount; $c += $sampleSize) {
        /** @var PathMap $pathMap */
        $clientCells = [];
        $totalProfit = 0;
        $worstProfit = 0;
        $clients = [];
        $cells = [];
        $possibleClients = [];
        $possibleCells = [];
        $revClients = [];
        foreach ($caches as $pathMap) {
            $cell = $pathMap->getCell($r, $c);
            if ($cell && $cell->pathCost > 0) {
                $profit = $pathMap->client->revenue - $cell->pathCost;
                if ($profit > 0) { // tuning
                    $totalProfit += $profit;
                    $clients[] = $pathMap->client;
                    $cells[] = $cell;
                    //$revClients[$pathMap->client->id] = 1;
                }
                $worstProfit += $profit;
                $possibleClients[] = $pathMap->client;
                $possibleCells[] = $cell;
                $foundClients[$pathMap->client->id] = 1;
            }
        }
        $possibilities->add([
            'r' => $r,
            'c' => $c,
            'worstProfit' => $worstProfit,
            'totalProfit' => $totalProfit,
            'clients' => $clients,
            'cells' => $cells,
            'possibleClients' => $possibleClients,
            'possibleCells' => $possibleCells,
            //'revClients' => $revClients,
            'remainingClientsCount' => count($possibleClients),
        ]);
    }
}


$content = "";
$outputScore = 0;
$takenOffices = 0;

echo "Found clients = " . array_sum($foundClients) . "\n";

foreach ($possibilities->sortByDesc('totalProfit')->take($maxOfficesCount - 1) as $p) {
    foreach ($p['cells'] as $c) {
        $content .= $c . "\n";
    }
    foreach ($p['clients'] as $c) {
        $reverseClients[$c->id] = 0;
    }
    $outputScore += $p['totalProfit'];
}

print_r($reverseClients);

foreach ($possibilities as $k => $possibility) {
    $remainingClientsCount = 0;
    foreach ($possibility['possibleClients'] as $c) {
        $remainingClientsCount += $reverseClients[$c->id];
    }
    $possibilities[$k]['remainingClientsCount'] = $remainingClientsCount;
}

echo "Remaining = " . array_sum($reverseClients) . "\n";
while (array_sum($reverseClients) > 0) {
    //$clientsToExclude = [];
    $p = $possibilities->sortBy('worstProfit')->sortBy('remainingClientsCount')->pop();

    echo "Current output points = $outputScore // Found remainingClientsCount=" . $p['remainingClientsCount'] . "\n";

    foreach ($p['possibleClients'] as $c) {
        //$clientsToExclude[] = $c->id;
        $reverseClients[$c->id] = 0;
    }
    foreach ($p['possibleCells'] as $c) {
        $content .= $c . "\n";
    }
    $takenOffices++;
    $outputScore += $p['worstProfit'];

    //print_r($p);

    foreach ($possibilities as &$possibility) {
        $remainingClientsCount = 0;
        foreach ($possibility['possibleClients'] as $c) {
            $remainingClientsCount += $reverseClients[$c->id];
        }
        $possibility['remainingClientsCount'] = $remainingClientsCount;
    }
    echo "Remaining = " . array_sum($reverseClients) . "\n";
}

$fileManager->output(trim($content));

echo "TOTAL Output Score no bonus = " . $outputScore . "\n";
echo "TOTAL Output Score with bonus = " . ($outputScore + $bonus) . "\n";


//foreach($possibilities->sortBy('totalProfit'))


/*
echo "Tot = " . count($sampledScores) . "\n";

foreach ($sampledScores as $k => $sample) {
    foreach ($sample['clients'] as $clientId)
        $reverseClients[$clientId] = 1;
    if (array_sum($reverseClients) == count($caches)) {
        echo "Trovato k = $k";
        die();
    }
}*/

/*
print_r($reverseClients);

$sampledScoresLimited = $sampledScores->sortByDesc('totalProfit')->take($maxOfficesCount);

$content = "";
$score = 0;

foreach ($sampledScoresLimited as $sample) {
    $score += $sample['totalProfit'];
    foreach ($sample['clientCells'] as $clientCell) {
        $content .= $clientCell['cell'] . "\n";
    }
}

$fileManager->output(trim($content));

echo "Score no bonus = " . $score;
*/
