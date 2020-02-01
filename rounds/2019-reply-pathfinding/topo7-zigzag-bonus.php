<?php

/*
 * Sampled algorithm
 * 2 OK best
 * 3 KO
 * 4 KO
 * 5 ?
 */

use Utils\ArrayUtils;
use Utils\Log;

$fileName = '3'; // try 4
$sampleSize = 1;

include 'reader.php';

/* recalculate the maxProfitClientsCountWhoAreMissing, missingClientsCount, missingClientsCount, missingClientsCount */
function alignPossibilities()
{
    global $remainingMissingClients;
    global $possibilities;
    global $nOffices;
    global $maxOfficesCount;

    $remainingOffices = $maxOfficesCount - $nOffices + 1;

    /* tuning */
    $km1 = 1;
    $kp1 = 1;
    $km2 = 1;
    $kp2 = 1;

    //if ($remainingOffices <= count($remainingMissingClients)) { //mod
    if ($remainingOffices == 1) { //mod
        $kp2 = 4;
    }
    /* end tuning */

    $currentMaxProfit = 0;

    foreach ($possibilities as &$p) {
        $maxProfitClientsCountWhoAreMissing = 0;
        $missingOrPositiveClientsCount = 0;
        $missingOrPositiveClients = [];
        $missingOrPositiveProfit = 0;
        foreach ($p['allClients'] as $c) {
            if ($remainingMissingClients[$c['id']] || $c['profit'] > 0) { //if missing or positive
                $missingOrPositiveProfit += $c['profit'];
                $missingOrPositiveClients[] = $c;
                $missingOrPositiveClientsCount++;
            }
            if ($remainingMissingClients[$c['id']] && $c['profit'] > 0) { //if missing AND positive (already in the maxProfitClients but also missing)
                $maxProfitClientsCountWhoAreMissing++;
            }
        }
        $p['maxProfitClientsCountWhoAreMissing'] = $maxProfitClientsCountWhoAreMissing;
        $p['missingOrPositiveClientsCount'] = $missingOrPositiveClientsCount;
        $p['missingOrPositiveClients'] = $missingOrPositiveClients;
        $p['missingOrPositiveProfit'] = $missingOrPositiveProfit;

        if ($p['maxProfit'] > $currentMaxProfit)
            $currentMaxProfit = $p['maxProfit'];
    }

    foreach ($possibilities as &$p) {
        /* the score is composed of the addition (of multiplied and powered by tuning) percentage of missingOrPositiveProfit respect the currentMaxProfit [+ the number of still missing clients with max profit divided by the number of remaining missing clients ONLY if there are remaining clients] */
        $sortScore = pow((1 + $p['missingOrPositiveProfit'] / $currentMaxProfit) * $km1, $kp1) + (count($remainingMissingClients) > 0 ? pow(1 + ($p['maxProfitClientsCountWhoAreMissing'] / count($remainingMissingClients)) * $km2, $kp2) : 0);
        $p['sortScore'] = $sortScore;
    }
}

function applyPossibility($possibility)
{
    global $contentOutput;
    global $outputScore;
    global $remainingMissingClients;
    foreach ($possibility['missingOrPositiveClients'] as $c) {
        $contentOutput .= $c['output'] . "\n";
        unset($remainingMissingClients[$c['id']]);
    }
    $outputScore += $possibility['missingOrPositiveProfit'];
}

/* global vars */
$remainingMissingClients = [];
$bonus = 0;
$contentOutput = "";
$outputScore = 0;


/** @var PathMap $pathMap */
foreach ($caches as $pathMap) {
    $bonus += $pathMap->client->revenue;
    $remainingMissingClients[$pathMap->client->id] = true;
}
Log::out('Bonus value = ' . $bonus);

Log::out('Starting to populate the possibilities');
$possibilities = [];
for ($r = 0; $r < $map->rowCount; $r += $sampleSize) {
    Log::out('R = ' . $r . '/' . $map->rowCount, 1);
    for ($c = 0; $c < $map->colCount; $c += $sampleSize) {
        /** @var PathMap $pathMap */

        $allClients = [];
        $maxProfitClients = [];
        $maxProfit = 0;

        foreach ($caches as $pathMap) {
            $cell = $pathMap->getCell($r, $c);
            if ($cell && $cell->pathCost > 0) { // not itself
                $profit = $pathMap->client->revenue - $cell->pathCost;

                $client = [
                    'id' => $pathMap->client->id,
                    'profit' => $profit,
                    'output' => (string)$cell,
                ];

                $allClients[] = $client;

                if ($profit > 0) {
                    $maxProfitClients[] = $client;
                    $maxProfit += $profit;
                }
            }
        }
        if (count($allClients) > 0) {
            $possibilities[] = [
                'r' => $r,
                'c' => $c,
                'allClients' => $allClients, // once
                'maxProfitClients' => $maxProfitClients, // once (only clients with profit > 0)
                'maxProfit' => $maxProfit, // once

                'maxProfitClientsCountWhoAreMissing' => 0, // missing clients within the maxProfitClients (in order to take clients from the top in a wise way) # iterated

                'missingOrPositiveClientsCount' => 0, // missing clients within the maxProfitClients (in order to take clients from the top in a wise way) # iterated
                'missingOrPositiveClients' => [], // missing + positive profit clients # iterated
                'missingOrPositiveProfit' => 0, // profit of (missing + prositive profit clients) # iterated
            ];
        }
    }
}

for ($nOffices = 1; $nOffices <= $maxOfficesCount; $nOffices++) {
    Log::out('Aligning the possibilities', 1);
    alignPossibilities();
    Log::out('Sorting the possibilities by sortScore DESC', 1);
    ArrayUtils::array_keysort($possibilities, 'sortScore', SORT_DESC);
    $possibility = array_shift($possibilities); // shifting out the best possibility (first) and excluding it from the array
    applyPossibility($possibility);
    Log::out('Offices: ' . $nOffices . '/ ' . $maxOfficesCount . ' & Remaining missing clients = ' . count($remainingMissingClients) . ' & Current score no bonus = ' . $outputScore . ' (' . ($outputScore + $bonus) . ' with bonus ~ est. ' . round($outputScore / $nOffices * $maxOfficesCount + $bonus) . ' final score projection)', 1);
}


$fileManager->output(trim($contentOutput));

echo "TOTAL Output Score no bonus = " . $outputScore . "\n";
echo "TOTAL Output Score with bonus = " . ($outputScore + $bonus) . "\n";
if (count($remainingMissingClients) == 0)
    echo "BONUS TAKEN!!!\n";
