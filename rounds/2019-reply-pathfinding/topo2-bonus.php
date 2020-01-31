<?php

/*
 * Sampled algorithm
 *
 */

$fileName = '2';
$sampleSize = 10;

include 'reader.php';

$remainingClients = [];
$reverseClients = [];
$firstClientOccurrence = [];

$bonus = 0;
/** @var PathMap $pathMap */
foreach ($caches as $pathMap) {
    $bonus += $pathMap->client->revenue;
    $reverseClients[$pathMap->client->id] = 0;
    $remainingClients[] = $pathMap->client->id;
}

$sampledScores = collect();
for ($r = 0; $r < $map->rowCount; $r += $sampleSize) {
    for ($c = 0; $c < $map->colCount; $c += $sampleSize) {
        /** @var PathMap $pathMap */
        $clientCells = [];
        echo "R=$r C=$c\n";
        $totalProfit = 0;
        foreach ($caches as $pathMap) {
            $cell = $pathMap->getCell($r, $c);
            if ($cell && $cell->pathCost > 0) {
                $profit = $pathMap->client->revenue - $cell->pathCost;
                if ($profit > -10000) {
                    $totalProfit += $profit;
                    $clientCells[] = [
                        'profit' => $profit,
                        'cell' => $cell,
                        'client' => $pathMap->client,
                    ];
                }
            }
        }
        if ($totalProfit > 0) {
            $sampledScores->add([
                'totalProfit' => $totalProfit,
                'clientCells' => $clientCells,
            ]);
        }
    }
}

foreach ($sampledScores as $k => $sample) {
    foreach($sample['clientCells'] as $clientCell)
        $reverseClients[$clientCell['client']->id] = 1;
    if(array_sum($reverseClients) == count($caches)) {
        echo "Trovato k = $k"; die();
    }
}

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
