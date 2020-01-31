<?php

error_reporting(0);

$fileName = '1';

include 'reader.php';

$scores = [];

for ($c = 0; $c < $colCount; $c++) {
    for ($r = 0; $r < $rowCount; $r++) {
        foreach ($caches as $cache) {
            /** @var PathMap $cache */
            if (isset($cache->cells[$r]) && isset($cache->cells[$r][$c]) && $cache->cells[$r][$c]->pathCost > 0) {
                if(!isset($scores["$r,$c"])) {
                    $scores["$r,$c"] = [
                        'score' => 0,
                        'reached' => [],
                    ];
                }
                $scores["$r,$c"] += $cache->client->revenue - $cache->cells[$r][$c]->pathCost;
                $scores['reached'][] = $cache->client->id;
            }
        }
    }
}
arsort($scores);

$covered = [];
for ($i = 0; $i < $clientsCount; $i++) {
    $covered = [];
}
foreach ($caches as $clientCache) {
    /** @var PathMap $clientCache */
    foreach ($scores as $rc => $s) {
        list($r, $c) = explode(',', $rc);
        if (isset($clientCache->cells[$r]) && isset($clientCache->cells[$r][$c])) {
            $taken["$r,$c"] = $clientCache->client->revenue - $clientCache->cells[$r][$c]->pathCost;
            break;
        }
    }
}










$final = array_slice($scores, 0, $maxOfficesCount);
print_r($final);

$taken = [];

foreach ($caches as $clientCache) {
    /** @var PathMap $clientCache */
    foreach ($scores as $rc => $s) {
        list($r, $c) = explode(',', $rc);
        if (isset($clientCache->cells[$r]) && isset($clientCache->cells[$r][$c])) {
            $taken[] = [
                'r' => $r,
                'c' => $c,
                'score' => $clientCache->client->revenue - $clientCache->cells[$r][$c]->pathCost,
                'path' => $clientCache->cells[$r][$c]->fixPath($clientCache->cells[$r][$c]->path),
            ];
        }
    }
}

$score = 0;
$output = '';
foreach ($taken as $t) {
    $score += $t['score'];
    $output .= $t['c'] . ' ' . $t['r'] . ' ' . $t['path'] . "\n";
}
echo "\n\nScore: $score\n\n";
$fileManager->output($output);

/*
foreach (array_keys($final) as $rc) {
    list($r, $c) = explode(',', $rc);
    foreach ($caches as $cache) {
        /** @var PathMap $cache *
        if (isset($cache->cells[$r]) && isset($cache->cells[$r][$c])) {
            $scores["$r,$c"] += $cache->client->revenue - $cache->cells[$r][$c]->pathCost;
        }
    }
}
*/

