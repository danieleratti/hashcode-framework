<?php

/*
 * Sampled algorithm
 *
 */

$fileName = '0';
$sampleSize = 1;

include 'reader.php';

$sampledScores = collect();
for ($r = 0; $r < $map->rowCount; $r += $sampleSize) {
    for ($c = 0; $c < $map->colCount; $c += $sampleSize) {
        /** @var PathMap $pathMap */
        foreach ($caches as $pathMap) {
            $cell = $pathMap->getCell($r, $c);
            if ($cell) {
                $profit = $pathMap->client->revenue - $cell->pathCost;
                if ($profit > 0) {
                    $sampledScores->add([
                        'profit' => $profit,
                        'cell' => $cell,
                        'client' => $pathMap->client,
                    ]);
                }
            }
        }
    }
}

$sampledScoresLimited = $sampledScores->sortByDesc('profit')->take($maxOfficesCount);

$x = 1;
