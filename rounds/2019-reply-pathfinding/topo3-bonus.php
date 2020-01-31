<?php

/*
 * Sampled algorithm
 *
 */

$fileName = '2';
$sampleSize = 10;

include 'reader.php';

$reverseClients = [];

$bonus = 0;
/** @var PathMap $pathMap */
foreach ($caches as $pathMap) {
    $bonus += $pathMap->client->revenue;
    $reverseClients[$pathMap->client->id] = 1;
}

$possibilities = collect();
for ($r = 0; $r < $map->rowCount; $r += $sampleSize) {
    for ($c = 0; $c < $map->colCount; $c += $sampleSize) {
        /** @var PathMap $pathMap */
        $clientCells = [];
        echo "R=$r C=$c\n";
        $totalProfit = 0;
        $clients = [];
        $revClients = [];
        foreach ($caches as $pathMap) {
            $cell = $pathMap->getCell($r, $c);
            if ($cell && $cell->pathCost > 0) {
                $profit = $pathMap->client->revenue - $cell->pathCost;
                if ($profit > -10000) { // tuning
                    $totalProfit += $profit;
                    $clients[] = $pathMap->client;
                    //$revClients[$pathMap->client->id] = 1;
                }
            }
        }
        if ($totalProfit > -10000) { // tuning
            $possibilities->add([
                'r' => $r,
                'c' => $c,
                'totalProfit' => $totalProfit,
                'clients' => $clients,
                //'revClients' => $revClients,
                'remainingClientsCount' => count($clients),
            ]);
        }
    }
}

while(array_sum($reverseClients) > 0) {
    //$clientsToExclude = [];
    $p = $possibilities->sortBy('remainingClientsCount')->pop(); //take the last. Add something like ->sortByAsc('totalProfit') (???????)
    foreach($p['clients'] as $c) {
        //$clientsToExclude[] = $c->id;
        $reverseClients[$c->id] = 0;
    }

    foreach($possibilities as &$possibility) {
        $remainingClientsCount = 0;
        foreach($possibility['clients'] as $c) {
            $remainingClientsCount += $reverseClients[$c->id];
        }
        $possibility['remainingClientsCount'] = $remainingClientsCount;
    }
}


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
