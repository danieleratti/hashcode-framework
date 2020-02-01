<?php

/*
 * Sampled algorithm
 * 2 OK best
 * 3 KO
 * 4 KO
 */

$fileName = '5';
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
        $revPossibleClients = [];
        $possibleClients = [];
        $possibleCells = [];
        $possibleProfits = [];
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
                $revPossibleClients[$pathMap->client->id] = true;
                $possibleCells[] = $cell;
                $possibleProfits[] = $profit;
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
            'possibleProfits' => $possibleProfits,
            'revPossibleClients' => $revPossibleClients,
            'remainingClientsCount' => count($possibleClients),
        ]);
    }
}


$content = "";
$outputScore = 0;
$paddingOffices = 1;

echo "Found clients = " . array_sum($foundClients) . "\n";

foreach ($possibilities->sortByDesc('totalProfit')->take($maxOfficesCount - $paddingOffices) as $key => $p) {
    foreach ($p['cells'] as $c) {
        $content .= $c . "\n";
    }
    foreach ($p['clients'] as $c) {
        $reverseClients[$c->id] = 0;
    }
    $outputScore += $p['totalProfit'];
    $possibilities->forget($key);
}

echo "Current score = " . $outputScore . "\n";
echo "Remaining = " . array_sum($reverseClients) . "\n";

print_r($reverseClients);

while (array_sum($reverseClients) > 0) {
    $paddingOffices--;
    if($paddingOffices < 0)
        die("Too few paddingOffices!!!");

    $bestPProfit = 0;
    $bestP = null;
    $bestPContent = "";
    foreach($possibilities as $p) {
        $currentContent = "";
        $currentProfit = 0;
        $ok = true;
        foreach($reverseClients as $clientId => $r) {
            if($r == 1 && !isset($p['revPossibleClients'][$clientId]))
                $ok = false;
        }
        if($ok) { // it has all the needed clients
            foreach($p['possibleClients'] as $key => $c) {
                if($reverseClients[$c->id] == 1 || $p['possibleProfits'][$key] > 0) {
                    $currentProfit += $p['possibleProfits'][$key];
                    $currentContent .= $p['possibleCells'][$key] . "\n";
                }
            }
            if($currentProfit > $bestPProfit) {
                $bestPProfit = $currentProfit;
                $bestP = $p;
                $bestPContent = $currentContent;
            }
        }
    }

    if(!$bestP)
        die("NO BestP found!!!");

    $content .= $bestPContent;
    $outputScore += $bestPProfit;
    $reverseClients = []; // D'ufficio!
}

$fileManager->output(trim($content));

echo "TOTAL Output Score no bonus = " . $outputScore . "\n";
echo "TOTAL Output Score with bonus = " . ($outputScore + $bonus) . "\n";

